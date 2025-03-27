<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="create-dialog">
		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<InputDiv ref="pollTitle"
				v-model="title"
				type="text"
				:placeholder="t('polls', 'Enter Title')"
				@submit="confirm" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll type')">
			<template #icon>
				<CheckIcon />
			</template>
			<RadioGroupDiv v-model="pollType" :options="pollTypeOptions" />
		</ConfigBox>

		<div class="create-buttons">
			<NcButton @click="cancel">
				<template #default>
					{{ t('polls', 'Cancel') }}
				</template>
			</NcButton>
			<NcButton :disabled="titleEmpty"
				type="primary"
				@click="confirm">
				<template #default>
					{{ t('polls', 'Apply') }}
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import { mapActions } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import { ConfigBox, RadioGroupDiv, InputDiv } from '../Base/index.js'
import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

export default {
	name: 'CreateDlg',

	components: {
		NcButton,
		SpeakerIcon,
		CheckIcon,
		ConfigBox,
		RadioGroupDiv,
		InputDiv,
	},

	data() {
		return {
			pollType: 'datePoll',
			title: '',
			pollTypeOptions: [
				{ value: 'datePoll', label: t('polls', 'Date poll') },
				{ value: 'textPoll', label: t('polls', 'Text poll') },
				{ value: 'textRankPoll', label: t('polls', 'Text Rank poll') },
			],
		}
	},

	computed: {
		titleEmpty() {
			return this.title === ''
		},
	},

	methods: {
		...mapActions({
			addPoll: 'poll/add',
		}),

		/** @public */
		setFocus() {
			this.$refs.pollTitle.setFocus()
		},

		cancel() {
			this.title = ''
			this.pollType = 'datePoll'
			this.$emit('close-create')
		},

		async confirm() {
			try {
				const response = await this.addPoll({ title: this.title, type: this.pollType })
				this.cancel()
				showSuccess(t('polls', 'Poll "{pollTitle}" added', { pollTitle: response.data.configuration.title }))
				this.$router.push({ name: 'vote', params: { id: response.data.id } })
			} catch {
				showError(t('polls', 'Error while creating Poll "{pollTitle}"', { pollTitle: this.title }))
			}
		},
	},
}
</script>

<style lang="css">
.create-dialog {
	background-color: var(--color-main-background);
	padding: 8px 20px;
}

.create-buttons {
	display: flex;
	justify-content: space-between;
}
</style>
