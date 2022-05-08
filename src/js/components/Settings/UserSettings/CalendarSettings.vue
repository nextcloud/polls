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
			<CheckboxRadioSwitch :checked.sync="calendarPeek" type="switch">
				{{ t('polls', 'Use calendar lookup for conflicting calendar events') }}
			</CheckboxRadioSwitch>
			<div v-show="calendarPeek" class="settings_details">
				{{ t('polls', 'Activate all calendars to consult for the search of conflicting events.') }}

				<div v-for="(calendar) in calendarChoices" :key="calendar.key" class="calendar-item">
					<CheckboxRadioSwitch :checked="calendar.selected"
						type="switch"
						@update:checked="clickedCalendar(calendar)">
						<span class="bully" :style="{ backgroundColor: calendar.displayColor }" />
						{{ calendar.name }}
					</CheckboxRadioSwitch>
				</div>
			</div>
		</div>

		<div class="user_settings">
			<p class="settings-description">
				{{ t('polls', 'Set the time interval in hours. A calendar event must be finished before a date option, to get accounted to the conflict check.') }}
			</p>
			<InputDiv v-model="checkCalendarsBefore"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				no-submit
				@add="checkCalendarsBefore += 1"
				@subtract="checkCalendarsBefore -= 1" />
		</div>

		<div class="user_settings">
			<p class="settings-description">
				{{ t('polls', 'Set the time interval in hours. A calendar event must start after a date option, to get accounted to the conflict check.') }}
			</p>
			<InputDiv v-model="checkCalendarsAfter"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				no-submit
				@add="checkCalendarsAfter += 1"
				@subtract="checkCalendarsAfter -= 1" />
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { CheckboxRadioSwitch } from '@nextcloud/vue'
import InputDiv from '../../Base/InputDiv.vue'

export default {
	name: 'CalendarSettings',

	components: {
		CheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
			calendars: (state) => state.settings.availableCalendars,
		}),

		checkCalendarsBefore: {
			get() {
				return this.settings.checkCalendarsBefore
			},
			set(value) {
				if (value < 0) {
					value = 24
				}
				if (value > 24) {
					value = 0
				}
				this.writeValue({ checkCalendarsBefore: +value })
			},
		},

		checkCalendarsAfter: {
			get() {
				return this.settings.checkCalendarsAfter
			},
			set(value) {
				if (value < 0) {
					value = 24
				}
				if (value > 24) {
					value = 0
				}
				this.writeValue({ checkCalendarsAfter: +value })
			},
		},

		calendarPeek: {
			get() {
				return !!this.settings.calendarPeek
			},
			set(value) {
				this.writeValue({ calendarPeek: value })
			},
		},

		calendarChoices() {
			return this.calendars.map((calendar) => ({
				key: calendar.key.toString(),
				name: calendar.name,
				displayColor: calendar.displayColor,
				selected: this.settings.checkCalendars.includes(calendar.key.toString()),
			}), this)
		},

	},

	methods: {
		async writeValue(value) {
			await this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},

		async clickedCalendar(calendar) {
			if (this.settings.checkCalendars.includes(calendar.key)) {
				await this.writeValue({ checkCalendars: this.settings.checkCalendars.filter((item) => item !== calendar.key.toString()) })
			} else {
				await this.$store.commit('settings/addCheckCalendar', { calendar })
			}
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
		margin-left: 36px;
	}

	.bully {
		display: inline-block;
		width: 11px;
		height: 11px;
		border-radius: 50%;
		margin: 0 4px 0 0;
	}
</style>
