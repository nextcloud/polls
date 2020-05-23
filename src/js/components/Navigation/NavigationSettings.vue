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
		<input id="experimental" v-model="experimental"
			type="checkbox" class="checkbox">
		<label for="experimental">{{ t('polls', 'Activate experimental settings.') }}</label>

		<div v-if="experimental">
			<input id="useimage" v-model="useImage"
				type="checkbox" class="checkbox">
			<label for="useimage">{{ t('polls', 'Use background image.') }}</label>

			<!-- <input v-if="bgImage" v-model="imageurl" type="text"> -->
			<input id="glassyNavigation" v-model="glassyNavigation"
				type="checkbox" class="checkbox">
			<label for="glassyNavigation">{{ t('polls', 'Glassy navigation.') }}</label>
			<input id="glassySidebar" v-model="glassySidebar"
				type="checkbox" class="checkbox">
			<label for="glassySidebar">{{ t('polls', 'Glassy sidebar.') }}</label>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
export default {
	name: 'NavigationSettings',

	computed: {
		...mapState({
			settings: state => state.settings.user,
		}),
		// Add bindings
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
		writeValue(value) {
			this.$store.commit('setUserSetting', value)
			this.$store.dispatch('writeSetting')
		},
	},
}
</script>
