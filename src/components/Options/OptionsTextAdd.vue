<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import InputDiv from '../Base/modules/InputDiv.vue'
import { SimpleOption, useOptionsStore } from '../../stores/options.ts'
import { AxiosError } from '@nextcloud/axios'

const optionsStore = useOptionsStore()

const { placeholder = t('polls', 'Add option') } = defineProps<{
	placeholder?: string
}>()

const newPollText = ref('')

/**
 *
 */
async function addOption(): Promise<void> {
	if (newPollText.value) {
		try {
			await optionsStore.add({ text: newPollText.value } as SimpleOption)
			showSuccess(
				t('polls', '{optionText} added', { optionText: newPollText.value }),
			)
			newPollText.value = ''
		} catch (error) {
			if ((error as AxiosError).response?.status === 409) {
				showError(
					t('polls', '{optionText} already exists', {
						optionText: newPollText.value,
					}),
				)
			} else {
				showError(
					t('polls', 'Error adding {optionText}', {
						optionText: newPollText.value,
					}),
				)
			}
		}
	}
}
</script>

<template>
	<InputDiv
		v-model="newPollText"
		:placeholder="placeholder"
		submit
		@submit="addOption()" />
</template>

<style lang="scss">
.optionAdd {
	display: flex;
}

.newOption {
	margin-inline-start: 40px;
	flex: 1;
	&:empty:before {
		color: grey;
	}
}

.submit-option {
	width: 30px;
	background-color: transparent;
	border: none;
	opacity: 0.3;
	cursor: pointer;
}
</style>
