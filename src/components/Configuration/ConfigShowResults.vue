<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<RadioGroupDiv v-model="pollStore.configuration.showResults" 
			:options="pollShowResultsOptions" 
			@update="pollStore.write()" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { RadioGroupDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigShowResults',

	components: {
		RadioGroupDiv,
	},

	data() {
		return {
			pollShowResultsOptions: [
				{ value: 'always', label: t('polls', 'Always show results') },
				{ value: 'closed', label: t('polls', 'Hide results until poll is closed') },
				{ value: 'never', label: t('polls', 'Never show results') },
			],
		}
	},

	computed: {
		...mapStores(usePollStore),
	},
}
</script>
