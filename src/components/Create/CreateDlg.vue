<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'

import SpeakerIcon from 'vue-material-design-icons/BullhornOutline.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

import InputDiv from '../Base/modules/InputDiv.vue'
import RadioGroupDiv from '../Base/modules/RadioGroupDiv.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'

import { usePollStore, pollTypes } from '../../stores/poll'

import { PollType } from '../../stores/poll.types'

const pollStore = usePollStore()
const router = useRouter()

const title = ref('')
const adding = ref(false)
const pollType = ref<PollType>('datePoll')

const pollTypeOptions = Object.entries(pollTypes).map(([key, value]) => ({
	value: key,
	label: value.name,
}))

const titleEmpty = computed(() => title.value === '')
const disableConfirm = computed(() => titleEmpty.value || adding.value)

const emit = defineEmits(['cancel', 'add'])

/**
 *
 */
function resetInput() {
	title.value = ''
	pollType.value = 'datePoll'
}

/**
 *
 */
async function add() {
	try {
		adding.value = true
		const poll = await pollStore.add({
			type: pollType.value,
			title: title.value,
		})

		resetInput()
		if (poll) {
			showSuccess(
				t('polls', 'Poll "{pollTitle}" added', {
					pollTitle: poll.configuration.title,
				}),
			)

			emit('add')

			router.push({
				name: 'vote',
				params: { id: poll.id },
			})
		}
	} catch {
		showError(
			t('polls', 'Error while creating Poll "{pollTitle}"', {
				pollTitle: title.value,
			}),
		)
	} finally {
		adding.value = false
	}
}
</script>

<template>
	<div class="create-dialog">
		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<InputDiv
				v-model="title"
				focus
				type="text"
				:placeholder="t('polls', 'Enter title')"
				@submit="add()" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll type')">
			<template #icon>
				<CheckIcon />
			</template>
			<RadioGroupDiv v-model="pollType" :options="pollTypeOptions" />
		</ConfigBox>

		<div class="create-buttons">
			<NcButton @click="emit('cancel')">
				<template #default>
					{{ t('polls', 'Cancel') }}
				</template>
			</NcButton>
			<NcButton :disabled="disableConfirm" :variant="'primary'" @click="add()">
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
