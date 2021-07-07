<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
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
		<div class="user_settings">
			{{ t('polls', 'Limit the amount of vote cells. If the threshold is reached, all other participants get hidden to avoid performance break downs. The default value is 1000.') }}
			<input v-model="threshold"
				type="text"
				:placeholder="t('polls', 'Enter amount of maximum allowed vote boxes.')">
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'

export default {
	name: 'PerformanceSettings',

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		threshold: {
			get() {
				return this.settings.performanceThreshold
			},
			set(value) {
				this.writeValue({ performanceThreshold: +value })
			},
		},
	},

	methods: {
		async writeValue(value) {
			await this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},
	},
}
</script>

<style>
	.user_settings {
		padding-top: 16px;
	}

	.settings_details {
		padding-top: 8px;
		margin-left: 26px;
	}

</style>
