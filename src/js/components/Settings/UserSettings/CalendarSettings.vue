<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="calendarPeek" type="switch">
				{{ t('polls', 'Use calendar lookup for conflicting calendar events') }}
			</NcCheckboxRadioSwitch>

			<div v-show="calendarPeek" class="settings_details">
				{{ t('polls', 'Select the calendars to use for lookup.') }}

				<div v-for="(calendar) in calendarChoices" :key="calendar.key" class="calendar-item">
					<NcCheckboxRadioSwitch :checked="calendar.selected"
						type="switch"
						@update:checked="clickedCalendar(calendar)">
						<span class="bully" :style="{ backgroundColor: calendar.displayColor }" />
						{{ calendar.name }}
					</NcCheckboxRadioSwitch>
				</div>
			</div>
		</div>

		<div class="user_settings">
			<InputDiv v-model="checkCalendarsBefore"
				:label="t('polls', 'Specify in which period (in hours) before the option existing appointments should be included in the search results.')"
				type="number"
				inputmode="numeric"
				use-num-modifiers />
		</div>

		<div class="user_settings">
			<InputDiv v-model="checkCalendarsAfter"
				:label="t('polls', 'Specify in which period (in hours) after the option existing appointments should be included in the search results.')"
				type="number"
				inputmode="numeric"
				use-num-modifiers />
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../../Base/index.js'

export default {
	name: 'CalendarSettings',

	components: {
		NcCheckboxRadioSwitch,
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
	.bully {
		display: inline-block;
		width: 11px;
		height: 11px;
		border-radius: 50%;
		margin: 0 4px 0 0;
	}
</style>
