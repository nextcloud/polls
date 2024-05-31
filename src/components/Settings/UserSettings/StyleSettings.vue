<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<b> {{ t('polls', 'The style settings are still experimental!') }}</b>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="useCommentsAlternativeStyling" type="switch">
				{{ t('polls', 'Use alternative styling for the comments sidebar') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="useAlternativeStyling" type="switch">
				{{ t('polls', 'Use alternative vote page styling') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'StyleSettings',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		useCommentsAlternativeStyling: {
			get() {
				return !!this.settings.useCommentsAlternativeStyling
			},
			set(value) {
				this.writeValue({ useCommentsAlternativeStyling: +value })
			},
		},

		useAlternativeStyling: {
			get() {
				return !!this.settings.useAlternativeStyling
			},
			set(value) {
				this.writeValue({ useAlternativeStyling: +value })
			},
		},

	},

	methods: {
		t,
		async writeValue(value) {
			await this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},
	},
}
</script>
