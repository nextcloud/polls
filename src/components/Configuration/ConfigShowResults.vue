<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import RadioGroupDiv from '../Base/modules/RadioGroupDiv.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

const emit = defineEmits(['change'])

const pollStore = usePollStore()

const pollShowResultsOptions = [
	{
		value: 'always',
		label: t('polls', 'Always show results'),
	},
	{
		value: 'closed',
		label: t('polls', 'Hide results until poll is closed'),
	},
	{
		value: 'never',
		label: t('polls', 'Never show results'),
	},
]
</script>

<template>
	<div>
		<RadioGroupDiv
			v-model="pollStore.configuration.showResults"
			:options="pollShowResultsOptions"
			@update="emit('change')" />
	</div>
</template>
