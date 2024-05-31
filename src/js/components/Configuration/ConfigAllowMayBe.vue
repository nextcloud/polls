<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch :checked.sync="allowMaybe" type="switch">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'ConfigAllowMayBe',

	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			label: t('polls', 'Allow "Maybe" vote'),
		}
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		allowMaybe: {
			get() {
				return this.pollConfiguration.allowMaybe
			},
			set(value) {
				this.$store.commit('poll/setProperty', { allowMaybe: value })
				this.$emit('change')
			},
		},

	},
}
</script>
