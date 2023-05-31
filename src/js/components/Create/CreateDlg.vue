<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template lang="html">
	<div class="create-dialog">
		<ConfigBox :title="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<InputDiv ref="pollTitle"
				v-model="title"
				type="text"
				:placeholder="t('polls', 'Enter Title')"
				@submit="confirm" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Poll type')">
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
			<NcButton :disabled="titleEmpty" type="primary" @click="confirm">
				<template #default>
					{{ t('polls', 'Apply') }}
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import ConfigBox from '../Base/ConfigBox.vue'
import RadioGroupDiv from '../Base/RadioGroupDiv.vue'
import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import InputDiv from '../Base/InputDiv.vue'

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

	emits: {
		'close-create': null,
	},

	data() {
		return {
			pollType: 'datePoll',
			title: '',
			pollTypeOptions: [
				{ value: 'datePoll', label: t('polls', 'Date poll') },
				{ value: 'textPoll', label: t('polls', 'Text poll') },
			],
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		titleEmpty() {
			return this.title === ''
		},
	},

	methods: {
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
				const response = await this.$store.dispatch('poll/add', { title: this.title, type: this.pollType })
				this.cancel()
				showSuccess(t('polls', 'Poll "{pollTitle}" added', { pollTitle: response.data.title }))
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
