<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { t } from '@nextcloud/l10n'

import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

import { FlexSettings } from '../components/Base/index.js'
import {
	CalendarSettings,
	FeatureSettings,
	StyleSettings,
	PerformanceSettings,
} from '../components/Settings/UserSettings/index.js'
import { usePreferencesStore } from '../stores/preferences.ts'

const preferencesStore = usePreferencesStore()

const sections = {
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
		description: t(
			'polls',
			'Try to change these parameters to handle big polls',
		),
	},
	styleSettings: {
		name: t('polls', 'Experimental styles'),
		description: t('polls', 'Some visual styling options.'),
	},
}

onMounted(() => {
	preferencesStore.load()
	// preferencesStore.getCalendars()
})
</script>

<template>
	<FlexSettings>
		<NcSettingsSection v-bind="sections.calendarSettings">
			<CalendarSettings />
		</NcSettingsSection>
		<NcSettingsSection v-bind="sections.personalSettings">
			<FeatureSettings />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.performanceSettings">
			<PerformanceSettings />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.styleSettings">
			<StyleSettings />
		</NcSettingsSection>
	</FlexSettings>
</template>
