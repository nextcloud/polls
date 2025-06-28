<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { InputDiv } from '../Base/index.ts'
import { SignalingType } from '../../Types/index.ts'
import { t } from '@nextcloud/l10n'
import { usePollGroupsStore } from '../../stores/pollGroups.ts'

const emit = defineEmits(['change'])

const pollGroupsStore = usePollGroupsStore()

const inputProps = {
	placeholder: t('polls', 'Enter a description'),
	helperText: t('polls', 'Choose a description for the overview page'),
}
const pollGroupDescription = computed({
	get() {
		return pollGroupsStore.currentPollGroup?.description || ''
	},
	set(value: string) {
		pollGroupsStore.setCurrentPollGroup({
			...pollGroupsStore.currentPollGroup,
			description: value,
		})
	},
})

const checkTitle = computed(() =>
	pollGroupDescription.value ? SignalingType.None : SignalingType.Error,
)
</script>

<template>
	<textarea
		v-model="pollGroupDescription"
		class="input-textarea"
		:placeholder="inputProps.placeholder" />
	<p class="helper">
		{{ inputProps.helperText }}
	</p>
</template>

<style scoped>
.input-textarea {
	width: 99%;
	resize: vertical;
}
</style>
