<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="preferencesStore.calendarPeek" 
				type="switch"
				@change="preferencesStore.write()">
				{{ t('polls', 'Use calendar lookup for conflicting calendar events') }}
			</NcCheckboxRadioSwitch>

			<div v-show="preferencesStore.calendarPeek" class="settings_details">
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
			<InputDiv v-model="preferencesStore.checkCalendarsHoursBefore"
				:label="t('polls', 'Specify in which period (in hours) before the option existing appointments should be included in the search results.')"
				type="number"
				inputmode="numeric"
				num-min="0"
				num-max="24"
				num-wrap
				use-num-modifiers 
				@change="preferencesStore.write()" />
		</div>

		<div class="user_settings">
			<InputDiv v-model="preferencesStore.checkCalendarsHoursAfter"
				:label="t('polls', 'Specify in which period (in hours) after the option existing appointments should be included in the search results.')"
				type="number"
				inputmode="numeric"
				num-min="0"
				num-max="24"
				num-wrap
				use-num-modifiers 
				@change="preferencesStore.write()" />
		</div>
	</div>
</template>

<script>

import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
import { usePreferencesStore } from '../../../stores/preferences.ts'

export default {
	name: 'CalendarSettings',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapStores(usePreferencesStore),

		calendarChoices() {
			return this.preferencesStore.availableCalendars.map((calendar) => ({
				key: calendar.key.toString(),
				name: calendar.name,
				displayColor: calendar.displayColor,
				selected: this.preferencesStore.checkCalendars.includes(calendar.key.toString()),
			}), this)
		},

	},

	methods: {
		t,
		async clickedCalendar(calendar) {
			if (this.preferencesStore.checkCalendars.includes(calendar.key)) {
				await this.writePreference({ checkCalendars: this.preferencesStore.checkCalendars.filter((item) => item !== calendar.key.toString()) })
			} else {
				await this.preferencesStore.addCheckCalendar(calendar)
			}
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
