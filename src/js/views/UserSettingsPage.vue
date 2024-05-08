<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<FlexSettings>
		<NcSettingsSection :name="t('polls', 'Calendar check')"
			:description="t('polls', 'Search for conflicting calendar entries')">
			<CalendarSettings />
		</NcSettingsSection>
		<NcSettingsSection :name="t('polls', 'Personal preferences')"
			:description="t('polls', 'Set your personal preferences for the polls app')">
			<FeatureSettings />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Performance settings')"
			:description="t('polls', 'Try to change these parameters to handle big polls')">
			<PerformanceSettings />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Experimental styles')"
			:description="t('polls', 'Some visual styling options.')">
			<StyleSettings />
		</NcSettingsSection>
	</FlexSettings>
</template>

<script>

import { NcSettingsSection } from '@nextcloud/vue'
import { FlexSettings } from '../components/Base/index.js'
import { CalendarSettings, FeatureSettings, StyleSettings, PerformanceSettings } from '../components/Settings/UserSettings/index.js'

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

	created() {
		this.$store.dispatch('settings/getCalendars')
		this.$store.dispatch('settings/get')
	},
}
</script>
