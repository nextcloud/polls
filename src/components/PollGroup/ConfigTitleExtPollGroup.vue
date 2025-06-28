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

const pollGroupTitleExt = computed({
	get() {
		return pollGroupsStore.currentPollGroup?.titleExt || ''
	},
	set(value: string) {
		pollGroupsStore.setCurrentPollGroup({
			...pollGroupsStore.currentPollGroup,
			titleExt: value,
		})
	},
})
const inputProps = {
	placeholder: t('polls', 'Enter extended title'),
	helperText: t('polls', 'Optional choose a more meaningful title for the overview page'),
}

</script>

<template>
	<InputDiv
		v-model="pollGroupTitleExt"
		v-bind="inputProps"
		class="input-textarea"
		type="text"
		@change="emit('change')" />
</template>
