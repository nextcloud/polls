<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch :checked.sync="allowComment" type="switch">
		{{ t('polls', 'Allow Comments') }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'ConfigAllowComment',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		allowComment: {
			get() {
				return this.pollConfiguration.allowComment
			},
			set(value) {
				this.$store.commit('poll/setProperty', { allowComment: value })
				this.$emit('change')
			},
		},

	},
}
</script>
