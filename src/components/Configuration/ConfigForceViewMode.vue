<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import RadioGroupDiv from '../Base/modules/RadioGroupDiv.vue'
import { usePollStore } from '../../stores/poll.ts'

const emit = defineEmits(['change'])

const pollStore = usePollStore()

const pollForcedViewOptions = [
	{
		value: 'user-pref',
		label: t('polls', 'User preference'),
	},
	{
		value: 'table-view',
		label: t('polls', 'Table view'),
	},
	{
		value: 'list-view',
		label: t('polls', 'List view'),
	},
]
</script>

<template>
	<div>
		<RadioGroupDiv
			v-model="pollStore.configuration.forcedDisplayMode"
			:options="pollForcedViewOptions"
			@update="emit('change')" />
	</div>
</template>
