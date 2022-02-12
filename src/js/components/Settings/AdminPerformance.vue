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
	<div class="user_settings">
		<RadioGroupDiv v-model="updateType" :options="updateTypeOptions" />
	</div>
</template>

<script>

import { mapState } from 'vuex'
import RadioGroupDiv from '../Base/RadioGroupDiv'

export default {
	name: 'AdminPerformance',

	components: {
		RadioGroupDiv,
	},

	data() {
		return {
			updateTypeOptions: [
				{ value: 'longPolling', label: t('polls', 'Activate long polling for instant updates') },
				{ value: 'periodicPolling', label: t('polls', 'Activate periodic polling of updates from the client') },
				{ value: 'noPolling', label: t('polls', 'Disable automatic updates (reload app for updates)') },
			],
		}
	},

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),

		// Add bindings
		updateType: {
			get() {
				return this.appSettings.updateType
			},
			set(value) {
				this.writeValue({ updateType: value })
			},
		},
	},

	methods: {
		async writeValue(value) {
			await this.$store.commit('appSettings/set', value)
			this.$store.dispatch('appSettings/write')
		},
	},
}
</script>

<style lang="scss">
	.user_settings {
		padding-top: 16px;
	}

	.settings_details {
		padding-bottom: 16px;
		margin-left: 36px;
		input, .stretch {
			width: 100%;
		}
	}
</style>
