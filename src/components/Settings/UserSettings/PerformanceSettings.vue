<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<h3>
			{{ t('polls', 'A poll with many options and voters can have a heavy impact on client performance.') }}
			{{ t('polls', 'Set the amount of voting cells (options x participants) up to which all voting cells should be displayed.') }}
			{{ t('polls', 'If this threshold gets trespassed, only the current participant will be displayed, to avoid a performance breakdown.') }}
			{{ t('polls', 'The default threshold of 1000 should be a good and safe value.') }}
		</h3>
		<InputDiv v-model="threshold"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:placeholder="'1000'"
			:modifier-step-value="100"
			:modifier-min="200" />
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { InputDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'PerformanceSettings',

	components: {
		InputDiv,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		threshold: {
			get() {
				return this.settings.performanceThreshold
			},
			set(value) {
				if (value < 1) {
					value = 1000
				}
				this.writeValue({ performanceThreshold: +value })
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
