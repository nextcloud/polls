<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { InputDiv } from '../Base/index.ts'
import { SignalingType } from '../../Types/index.ts'
import { t } from '@nextcloud/l10n'
import { usePollGroupsStore } from '../../stores/pollGroups.ts'

const pollGroupsStore = usePollGroupsStore()

// updating the title also changes the slug
const updateTitle = () => {
	if (pollGroupTitle.value) {
		pollGroupsStore.setCurrentPollGroup({
			...pollGroupsStore.currentPollGroup,
			title: pollGroupTitle.value,
		})
		pollGroupsStore.writeCurrentPollGroup()
	}
}

const resetTitle = () => {
	pollGroupTitle.value = pollGroupsStore.currentPollGroup?.title || ''
}

const titleUpdated = computed(
	() => pollGroupTitle.value !== pollGroupsStore.currentPollGroup?.title,
)

const pollGroupTitle = ref(pollGroupsStore.currentPollGroup?.title || '')

const inputProps = {
	placeholder: t('polls', 'Enter Title'),
	helperText: t(
		'polls',
		'Choose a brief title for the navigation bar and the slug',
	),
	submit: true,
}

const titleChangedNote = t('polls', 'Note: Changing the title, also changes the URL')

const checkTitle = computed(() =>
	pollGroupTitle.value ? SignalingType.None : SignalingType.Error,
)
</script>

<template>
	<InputDiv
		v-model="pollGroupTitle"
		v-bind="inputProps"
		:signaling-class="checkTitle"
		type="text"
		@blur="resetTitle()"
		@submit="updateTitle()" />

	<div class="change-title-hint">
		<p v-if="titleUpdated">
			{{ titleChangedNote }}
		</p>
	</div>
</template>
