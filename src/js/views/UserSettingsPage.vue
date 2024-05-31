<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<FlexSettings>
		<NcSettingsSection v-bind="calendarSettings">
			<CalendarSettings />
		</NcSettingsSection>
		<NcSettingsSection v-bind="personalSettings">
			<FeatureSettings />
		</NcSettingsSection>

		<NcSettingsSection v-bind="performanceSettings">
			<PerformanceSettings />
		</NcSettingsSection>

		<NcSettingsSection v-bind="styleSettings">
			<StyleSettings />
		</NcSettingsSection>
	</FlexSettings>
</template>

<script>

import { NcSettingsSection } from '@nextcloud/vue'
import { FlexSettings } from '../components/Base/index.js'
import { CalendarSettings, FeatureSettings, StyleSettings, PerformanceSettings } from '../components/Settings/UserSettings/index.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'UserSettingsPage',

	components: {
		NcSettingsSection,
		FlexSettings,
		CalendarSettings,
		FeatureSettings,
		StyleSettings,
		PerformanceSettings,
	},

	data() {
		return {
			calendarSettings: {
				name: t('polls', 'Calendar check'),
				description: t('polls', 'Search for conflicting calendar entries'),
			},
			personalSettings: {
				name: t('polls', 'Personal preferences'),
				description: t('polls', 'Set your personal preferences for the polls app'),
			},
			performanceSettings: {
				name: t('polls', 'Performance settings'),
				description: t('polls', 'Try to change these parameters to handle big polls'),
			},
			styleSettings: {
				name: t('polls', 'Experimental styles'),
				description: t('polls', 'Some visual styling options.'),
			},
		}
	},

	created() {
		this.$store.dispatch('settings/getCalendars')
		this.$store.dispatch('settings/get')
	},
}
</script>
