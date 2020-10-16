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
	<div :open.sync="show">
		<div class="user_settings">
			<input id="calendarPeek" v-model="calendarPeek"
				type="checkbox" class="checkbox">
			<label for="calendarPeek">{{ t('polls', 'Use calendar lookup') }}</label>
			<div class="settings_description">
				{{ t('polls', 'Check, if an option in a date poll is conflicting with or near an entry in your calendar.') }}
			</div>
		</div>

		<div class="user_settings">
			<input id="defaultViewTextPoll" v-model="defaultViewTextPoll"
				type="checkbox" class="checkbox">
			<label for="defaultViewTextPoll">{{ t('polls', 'Text polls default to list view') }}</label>
			<div class="settings_description">
				{{ t('polls', 'Check this, if you prefer to display text poll in a vertical aligned list rather than in the grid view. The initial default is list view.') }}
			</div>
		</div>

		<div class="user_settings">
			<input id="defaultViewDatePoll" v-model="defaultViewDatePoll"
				type="checkbox" class="checkbox">
			<label for="defaultViewDatePoll">{{ t('polls', 'Date polls default to list view') }}</label>
			<div class="settings_description">
				{{ t('polls', 'Check this, if you prefer to display date poll in a vertical view rather than in the grid view. The initial default is grid view.') }}
			</div>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'

export default {
	name: 'FeatureSettings',

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
	},

	methods: {
		writeValue(value) {
			this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},
	},
}
</script>

<style>
	.user_settings {
		padding-top: 16px;
	}
	.settings_description {
		padding-top: 8px;
	}
</style>
