<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch :checked.sync="anonymous" type="switch">
		{{ t('polls', 'Anonymous poll') }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'ConfigAnonymous',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		anonymous: {
			get() {
				return this.pollConfiguration.anonymous
			},
			set(value) {
				this.$store.commit('poll/setProperty', { anonymous: value })
				this.$emit('change')
			},
		},
	},

	methods: {
		t,
	},
}
</script>
