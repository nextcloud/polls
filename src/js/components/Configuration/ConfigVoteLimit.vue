<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcCheckboxRadioSwitch :checked.sync="useVoteLimit" type="switch">
			{{ t('polls', 'Limit "Yes" votes per participant') }}
		</NcCheckboxRadioSwitch>

		<InputDiv v-if="maxVotesPerUser"
			v-model="maxVotesPerUser"
			class="indented"
			type="number"
			inputmode="numeric"
			use-num-modifiers />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../Base/index.js'

export default {
	name: 'ConfigVoteLimit',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
			countOptions: (state) => state.options.list.length,
		}),

		useVoteLimit: {
			get() {
				return (this.pollConfiguration.maxVotesPerUser !== 0)
			},
			set(value) {
				this.$store.commit('poll/setLimit', { maxVotesPerUser: value ? 1 : 0 })
				this.$emit('change')
			},
		},

		maxVotesPerUser: {
			get() {
				return this.pollConfiguration.maxVotesPerUser
			},
			set(value) {
				if (!this.useVoteLimit) {
					value = 0
				} else if (value < 1) {
					value = this.countOptions
				} else if (value > this.countOptions) {
					value = 1
				}
				this.$store.commit('poll/setLimit', { maxVotesPerUser: value })
				this.$emit('change')
			},
		},
	},
}
</script>
