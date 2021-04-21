<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<RadioGroupDiv v-model="pollShowResults" :options="pollShowResultsOptions" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import RadioGroupDiv from '../Base/RadioGroupDiv'

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
			poll: (state) => state.poll,
		}),

		pollShowResults: {
			get() {
				return this.poll.showResults
			},
			set(value) {
				this.$store.commit('poll/setProperty', { showResults: value })
				this.$emit('change')
			},
		},
	},
}
</script>
