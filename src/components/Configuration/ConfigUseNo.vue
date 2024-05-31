<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch :checked.sync="deleteVoteOnNo" type="switch">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'ConfigUseNo',

	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			label: t('polls', 'Delete vote when switched to "No"'),
		}
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		deleteVoteOnNo: {
			get() {
				return !this.pollConfiguration.useNo
			},
			set(value) {
				this.$store.commit('poll/setProperty', { useNo: !value })
				this.$emit('change')
			},
		},

	},
}
</script>
