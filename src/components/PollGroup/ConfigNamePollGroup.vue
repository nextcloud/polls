<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { InputDiv } from '../Base/index.ts'
import { t } from '@nextcloud/l10n'
import { usePollGroupsStore } from '../../stores/pollGroups.ts'
import { computed } from 'vue'
import { SignalingType } from '../../Types/index.ts'

const emit = defineEmits(['change'])

const pollGroupsStore = usePollGroupsStore()

const pollGroupName = computed({
	get() {
		return pollGroupsStore.currentPollGroup?.name || ''
	},
	set(value: string) {
		pollGroupsStore.setCurrentPollGroup({
			...pollGroupsStore.currentPollGroup,
			name: value,
		})
	},
})
const checkName = computed(() =>
	pollGroupsStore.currentPollGroup?.name
		? SignalingType.None
		: SignalingType.Error,
)

const inputProps = {
	placeholder: t('polls', 'Enter Title'),
	helperText: t(
		'polls',
		'Choose a brief title for the navigation bar and the slug',
	),
}
</script>

<template>
	<InputDiv
		v-model="pollGroupName"
		v-bind="inputProps"
		:signaling-class="checkName"
		type="text"
		@change="emit('change')" />
</template>
