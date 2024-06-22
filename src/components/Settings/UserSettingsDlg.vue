<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSettingsDialog :open.sync="show" :show-navigation="true">
		<NcAppSettingsSection id="calendar" :name="t('polls', 'Calendar check')">
			<CalendarSettings />
		</NcAppSettingsSection>

		<NcAppSettingsSection id="div-settings" :name="t('polls', 'Personal preferences')">
			<FeatureSettings />
		</NcAppSettingsSection>

		<NcAppSettingsSection id="performance" :name="t('polls', 'Performance settings')">
			<PerformanceSettings />
		</NcAppSettingsSection>

		<NcAppSettingsSection id="styles" :name="t('polls', 'Styles')">
			<StyleSettings />
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>

<script>

import { NcAppSettingsDialog, NcAppSettingsSection } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { CalendarSettings, FeatureSettings, StyleSettings, PerformanceSettings } from './UserSettings/index.js'
import { t } from '@nextcloud/l10n'
import { mapStores } from 'pinia'
import { usePreferencesStore } from '../../stores/preferences.ts'

export default {
	name: 'UserSettingsDlg',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		CalendarSettings,
		FeatureSettings,
		StyleSettings,
		PerformanceSettings,
	},

	data() {
		return {
			show: false,
		}
	},

	computed: {
		...mapStores(usePreferencesStore),
	},
	watch: {
		async show() {
			if (this.show === true) {
				this.preferencesStore.get()
				this.preferencesStore.getCalendars()
			}
		},
	},

	created() {
		subscribe('polls:settings:show', () => {
			this.show = true
		})

	},

	beforeDestroy() {
		unsubscribe('polls:settings:show')
	},
	
	methods: {
		t,
	},
}
</script>
