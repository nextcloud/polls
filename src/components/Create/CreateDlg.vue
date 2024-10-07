<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref, computed } from 'vue'
	import { useRouter } from 'vue-router'
	import { showSuccess, showError } from '@nextcloud/dialogs'
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	import { ConfigBox, RadioGroupDiv, InputDiv } from '../Base/index.js'
	import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
	import CheckIcon from 'vue-material-design-icons/Check.vue'
	import { t } from '@nextcloud/l10n'
	import { usePollStore, PollType } from '../../stores/poll.ts'

	const pollStore = usePollStore()
	const router = useRouter()

	const title = ref('')
	const pollType = ref(PollType.Date)
	const pollTypeOptions = [
		{ value: PollType.Date, label: t('polls', 'Date poll') },
		{ value: PollType.Text, label: t('polls', 'Text poll') },
	]

	const titleEmpty = computed(() => (title.value === ''))

	const emit = defineEmits(['closeCreate'])

	const cancel = () => {
		title.value = ''
		pollType.value = PollType.Date
		emit('closeCreate')
	}

	const confirm = async () => {
		try {
			const response = await pollStore.add({ title: title.value, type: pollType.value })
			cancel()
			showSuccess(t('polls', 'Poll "{pollTitle}" added', { pollTitle: response.data.configuration.title }))
			router.push({ name: 'vote', params: { id: response.data.id } })
		} catch {
			showError(t('polls', 'Error while creating Poll "{pollTitle}"', { pollTitle: title.value }))
		}
	}

</script>

<template>
	<div class="create-dialog">
		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<InputDiv v-model="title"
				focus
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
				:type="ButtonType.Primary"
				@click="confirm">
				<template #default>
					{{ t('polls', 'Apply') }}
				</template>
			</NcButton>
		</div>
	</div>
</template>

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
