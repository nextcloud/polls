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
		<NcCheckboxRadioSwitch :checked.sync="useOptionLimit" type="switch">
			{{ t('polls', 'Limit "Yes" votes per option') }}
		</NcCheckboxRadioSwitch>

		<InputDiv v-if="maxVotesPerOption"
			v-model="maxVotesPerOption"
			class="indented"
			type="number"
			inputmode="numeric"
			use-num-modifiers />

		<NcCheckboxRadioSwitch v-if="maxVotesPerOption"
			class="indented"
			:checked.sync="hideBookedUp"
			type="switch">
			{{ t('polls', 'Hide not available Options') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../Base/index.js'

export default {
	name: 'ConfigOptionLimit',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		useOptionLimit: {
			get() {
				return (this.poll.limits.maxVotesPerOption !== 0)
			},
			set(value) {
				this.$store.commit('poll/setLimit', { maxVotesPerOption: value ? 1 : 0 })
				this.$emit('change')
			},
		},

		maxVotesPerOption: {
			get() {
				return this.poll.limits.maxVotesPerOption
			},
			set(value) {
				if (!this.useOptionLimit) {
					value = 0
				} else if (value < 1) {
					value = 1
				}
				this.$store.commit('poll/setLimit', { maxVotesPerOption: value })
				this.$emit('change')
			},
		},

		hideBookedUp: {
			get() {
				return (this.poll.hideBookedUp > 0)
			},
			set(value) {
				this.$store.commit('poll/setProperty', { hideBookedUp: +value })
				this.$emit('change')
			},
		},

	},
}
</script>
