<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<RadioGroupDiv v-model="pollShowResults" :options="pollShowResultsOptions" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { RadioGroupDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'

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
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		pollShowResults: {
			get() {
				return this.pollConfiguration.showResults
			},
			set(value) {
				this.$store.commit('poll/setProperty', { showResults: value })
				this.$emit('change')
			},
		},
	},
}
</script>
