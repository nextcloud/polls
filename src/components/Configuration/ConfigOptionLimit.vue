<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
import { t } from '@nextcloud/l10n'

export default {
	name: 'ConfigOptionLimit',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		useOptionLimit: {
			get() {
				return (this.pollConfiguration.maxVotesPerOption !== 0)
			},
			set(value) {
				this.$store.commit('poll/setLimit', { maxVotesPerOption: value ? 1 : 0 })
				this.$emit('change')
			},
		},

		maxVotesPerOption: {
			get() {
				return this.pollConfiguration.maxVotesPerOption
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
				return (this.pollConfiguration.hideBookedUp)
			},
			set(value) {
				this.$store.commit('poll/setProperty', { hideBookedUp: value })
				this.$emit('change')
			},
		},
	},
	
	methods: {
		t,
	},
}
</script>
