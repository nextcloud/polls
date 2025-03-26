<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { InputDiv } from '../../Base/index.ts'
import { usePreferencesStore } from '../../../stores/preferences.ts'
const preferencesStore = usePreferencesStore()

const calendarChoices = computed(() =>
	preferencesStore.availableCalendars.map((calendar) => ({
		key: calendar.key.toString(),
		name: calendar.name,
		displayColor: calendar.displayColor,
		selected: preferencesStore.user.checkCalendars.includes(
			calendar.key.toString(),
		),
	})),
)

const clickedCalendar = (calendar) => {
	if (preferencesStore.user.checkCalendars.includes(calendar.key)) {
		preferencesStore.removeCheckCalendar(calendar)
	} else {
		preferencesStore.addCheckCalendar(calendar)
	}
}
</script>

<template>
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.calendarPeek"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{
					t('polls', 'Use calendar lookup for conflicting calendar events')
				}}
			</NcCheckboxRadioSwitch>

			<div
				v-show="preferencesStore.user.calendarPeek"
				class="settings_details">
				{{ t('polls', 'Select the calendars to use for lookup.') }}

				<div
					v-for="calendar in calendarChoices"
					:key="calendar.key"
					class="calendar-item">
					<NcCheckboxRadioSwitch
						:model-value="calendar.selected"
						type="switch"
						@update:model-value="clickedCalendar(calendar)">
						<span
							class="bully"
							:style="{ backgroundColor: calendar.displayColor }" />
						{{ calendar.name }}
					</NcCheckboxRadioSwitch>
				</div>
			</div>
		</div>

		<div class="user_settings">
			<InputDiv
				v-model="preferencesStore.user.checkCalendarsHoursBefore"
				:label="
					t(
						'polls',
						'Specify in which period (in hours) before the option existing appointments should be included in the search results.',
					)
				"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				:num-min="0"
				:num-max="24"
				num-wrap
				@change="preferencesStore.write()" />
		</div>

		<div class="user_settings">
			<InputDiv
				v-model="preferencesStore.user.checkCalendarsHoursAfter"
				:label="
					t(
						'polls',
						'Specify in which period (in hours) after the option existing appointments should be included in the search results.',
					)
				"
				type="number"
				inputmode="numeric"
				:num-min="0"
				:num-max="24"
				num-wrap
				use-num-modifiers
				@change="preferencesStore.write()" />
		</div>
	</div>
</template>

<style>
.bully {
	display: inline-block;
	width: 11px;
	height: 11px;
	border-radius: 50%;
	margin: 0 4px 0 0;
}
</style>
