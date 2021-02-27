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
			<CheckBoxDiv v-model="experimental" :label="t('polls', 'Try experimental styles')" />
			<div class="settings_details">
				{{ t('polls', 'Some experimental UI variants. Changes the background color of the main area.') }}
			</div>
		</div>

		<div v-if="experimental">
			<div class="user_settings">
				<CheckBoxDiv v-model="useImage" :label="t('polls', 'Use background image')" />
				<div class="settings_details">
					{{ t('polls', 'Add a background image to the main area.') }}
					<input v-if="useImage" v-model="imageUrl" type="text">
					<div v-if="useImage">
						{{ t('polls', 'Enter the URL of your favorite background image.') }}
					</div>
				</div>
			</div>

			<div class="user_settings">
				<CheckBoxDiv v-model="glassyNavigation" :label="t('polls', 'Glassy navigation')" />
				<div class="settings_details">
					{{ t('polls', 'Blurs the background of the navigation (Does not work with all browsers).') }}
				</div>
			</div>

			<div class="user_settings">
				<CheckBoxDiv v-model="glassySidebar" :label="t('polls', 'Glassy sidebar')" />
				<div class="settings_details">
					{{ t('polls', 'Blurs the background of the sidebar (Does not work with all browsers).') }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import CheckBoxDiv from '../Base/CheckBoxDiv'

export default {
	name: 'ExpertimantalSettings',

	components: {
		CheckBoxDiv,
	},

	props: {
		show: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapState({
			settings: state => state.settings.user,
		}),
		// Add bindings
		realTimePolling: {
			get() {
				return this.settings.realTimePolling
			},
			set(value) {
				this.writeValue({ realTimePolling: value })
			},
		},
		experimental: {
			get() {
				return this.settings.experimental
			},
			set(value) {
				this.writeValue({ experimental: value })
			},
		},
		useImage: {
			get() {
				return this.settings.useImage
			},
			set(value) {
				this.writeValue({ useImage: value })
			},
		},
		imageUrl: {
			get() {
				return this.settings.imageUrl
			},
			set(value) {
				this.writeValue({ imageUrl: value })
			},
		},
		glassyNavigation: {
			get() {
				return this.settings.glassyNavigation
			},
			set(value) {
				this.writeValue({ glassyNavigation: value })
			},
		},
		glassySidebar: {
			get() {
				return this.settings.glassySidebar
			},
			set(value) {
				this.writeValue({ glassySidebar: value })
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
