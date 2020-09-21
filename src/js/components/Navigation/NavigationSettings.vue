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
		<input id="calendarPeek" v-model="calendarPeek"
			type="checkbox" class="checkbox">
		<label for="calendarPeek">{{ t('polls', 'Use calendar lookup') }}</label>

		<input id="defaultViewTextPoll" v-model="defaultViewTextPoll"
			type="checkbox" class="checkbox">
		<label for="defaultViewTextPoll">{{ t('polls', 'Text polls default to table view') }}</label>

		<input id="defaultViewDatePoll" v-model="defaultViewDatePoll"
			type="checkbox" class="checkbox">
		<label for="defaultViewDatePoll">{{ t('polls', 'Date polls default to table view') }}</label>

		<input id="experimental" v-model="experimental"
			type="checkbox" class="checkbox">
		<label for="experimental">{{ t('polls', 'Try experimental styles') }}</label>

		<div v-if="experimental">
			<input id="useImage" v-model="useImage"
				type="checkbox" class="checkbox">
			<label for="useImage">{{ t('polls', 'Use background image') }}</label>

			<input v-if="useImage" v-model="imageUrl" type="text">

			<input id="glassyNavigation" v-model="glassyNavigation"
				type="checkbox" class="checkbox">
			<label for="glassyNavigation">{{ t('polls', 'Glassy navigation') }}</label>

			<input id="glassySidebar" v-model="glassySidebar"
				type="checkbox" class="checkbox">
			<label for="glassySidebar">{{ t('polls', 'Glassy sidebar') }}</label>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'

export default {
	name: 'NavigationSettings',

	data() {
		return {
			viewOptions: [
				'desktop',
				'mobile',
			],
		}
	},

	computed: {
		...mapState({
			settings: state => state.settings.user,
		}),
		// Add bindings
		calendarPeek: {
			get() {
				return this.settings.calendarPeek
			},
			set(value) {
				this.writeValue({ calendarPeek: value })
			},
		},
		defaultViewTextPoll: {
			get() {
				return (this.settings.defaultViewTextPoll === 'mobile')
			},
			set(value) {
				if (value) {
					this.writeValue({ defaultViewTextPoll: 'mobile' })
				} else {
					this.writeValue({ defaultViewTextPoll: 'desktop' })
				}
			},
		},
		defaultViewDatePoll: {
			get() {
				return (this.settings.defaultViewDatePoll === 'mobile')
			},
			set(value) {
				if (value) {
					this.writeValue({ defaultViewDatePoll: 'mobile' })
				} else {
					this.writeValue({ defaultViewDatePoll: 'desktop' })
				}
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
		writeValue(value) {
			this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},
	},
}
</script>
