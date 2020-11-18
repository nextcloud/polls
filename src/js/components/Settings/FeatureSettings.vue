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
			<input id="calendarPeek" v-model="calendarPeek"
				type="checkbox" class="checkbox">
			<label for="calendarPeek">{{ t('polls', 'Use calendar lookup') }}</label>
			<div class="settings_details">
				{{ t('polls', 'Check, if an option in a date poll is conflicting with or near an entry in your calendar.') }}
				{{ t('polls', 'Opt in to the calendars, which should be checked.') }}

				<div v-for="(calendar) in calendarChoices" :key="calendar.key" class="calendar-item">
					<input :id="'calendar_' + calendar.key"
						v-model="calendar.selected"
						type="checkbox"
						class="checkbox"
						@click="clickedCalendar(calendar)">
					<label :for="'calendar_' + calendar.key" class="calendar-checkbox">
						<span class="bully" :style="{ backgroundColor: calendar.displayColor }" />
						<span>{{ calendar.name }}</span>
					</label>
				</div>
			</div>
		</div>

		<div class="user_settings">
			<input id="defaultViewTextPoll" v-model="defaultViewTextPoll"
				type="checkbox" class="checkbox">
			<label for="defaultViewTextPoll">{{ t('polls', 'Text polls default to list view') }}</label>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display text poll in a vertical aligned list rather than in the grid view. The initial default is list view.') }}
			</div>
		</div>

		<div class="user_settings">
			<input id="defaultViewDatePoll" v-model="defaultViewDatePoll"
				type="checkbox" class="checkbox">
			<label for="defaultViewDatePoll">{{ t('polls', 'Date polls default to list view') }}</label>
			<div class="settings_details">
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
			calendars: state => state.settings.availableCalendars,
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

		calendarChoices() {
			var list = []
			this.calendars.forEach((calendar) => {
				// console.log(calendar.key.toString())
				// console.log(this.settings.checkCalendars)
				// console.log(this.settings.checkCalendars.includes(calendar.key.toString()))

				list.push({
					key: calendar.key.toString(),
					name: calendar.name,
					displayColor: calendar.displayColor,
					selected: this.settings.checkCalendars.includes(calendar.key.toString()),
				})
			})

			return list
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

		clickedCalendar(calendar) {
			// console.log(calendar.key)
			// console.log(checkCalendars)
			if (this.settings.checkCalendars.includes(calendar.key)) {
				// console.log(this.settings.checkCalendars)
				// console.log('removed', this.settings.checkCalendars.filter(item => item !== calendar.key.toString()))
				this.writeValue({ checkCalendars: this.settings.checkCalendars.filter(item => item !== calendar.key.toString()) })
			} else {
				this.$store.commit('settings/addCheckCalendar', { calendar: calendar })
				// this.writeValue({ checkCalendars: checkCalendars })
			}
			// console.log(this.settings.checkCalendars)
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

	.bully {
		display: inline-block;
		width: 11px;
		height: 11px;
		border-radius: 50%;
		margin: 0 4px 0 0;
	}
</style>
