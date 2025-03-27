<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'

import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

import { ConfigBox, RadioGroupDiv, InputDiv } from '../Base/index.ts'

import { PollType, usePollStore } from '../../stores/poll.ts'
import { showError, showSuccess } from '@nextcloud/dialogs'

const pollStore = usePollStore()

const emit = defineEmits<{
	(e: 'close'): void
	(e: 'added', poll: { id: number; title: string }): void
}>()

const pollTitle = ref('')
const pollType = ref(PollType.Date)
const pollId = ref(null as number | null)
const adding = ref(false)

const pollTypeOptions = [
	{ value: PollType.Date, label: t('polls', 'Date poll') },
	{ value: PollType.Text, label: t('polls', 'Text poll') },
]

const titleIsEmpty = computed(() => pollTitle.value === '')
const disableAddButton = computed(() => titleIsEmpty.value || adding.value)

async function addPoll() {
	try {
		// block the modal to prevent double submission
		adding.value = true
		// add the poll
		const response = await pollStore.add({
			title: pollTitle.value,
			type: pollType.value,
		})
		pollId.value = response.data.id

		showSuccess(
			t('polls', '"{pollTitle}" has been added', {
				pollTitle: response.data.configuration.title,
			}),
		)
		emit('added', {
			id: response.data.id,
			title: response.data.configuration.title,
		})
		resetPoll()
	} catch {
		showError(
			t('polls', 'Error while creating Poll "{pollTitle}"', {
				pollTitle: pollTitle.value,
			}),
		)
	} finally {
		// unblock the modal
		adding.value = false
	}
}

function resetPoll() {
	pollId.value = null
	pollTitle.value = ''
}
</script>

<template>
	<div class="create-dialog">
		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<InputDiv
				v-model="pollTitle"
				focus
				type="text"
				:placeholder="t('polls', 'Enter Title')"
				:helper-text="t('polls', 'Choose a meaningful title for your poll')"
				@submit="addPoll" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll type')">
			<template #icon>
				<CheckIcon />
			</template>
			<RadioGroupDiv v-model="pollType" :options="pollTypeOptions" />
		</ConfigBox>

		<div class="create-buttons">
			<NcButton @click="emit('close')">
				<template #default>
					{{ t('polls', 'Close') }}
				</template>
			</NcButton>
			<NcButton
				:disabled="disableAddButton"
				:type="ButtonType.Primary"
				@click="addPoll">
				<template #default>
					{{ t('polls', 'Add') }}
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
	justify-content: flex-end;
	gap: 8px;
}
</style>
