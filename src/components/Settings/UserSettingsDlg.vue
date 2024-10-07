<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup>
	import { ref, onMounted, onUnmounted } from 'vue'
	import { subscribe, unsubscribe } from '@nextcloud/event-bus'
	import { t } from '@nextcloud/l10n'

	import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
	import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'

	import { CalendarSettings, FeatureSettings, StyleSettings, PerformanceSettings } from './UserSettings/index.js'
	import { usePreferencesStore } from '../../stores/preferences.ts'

	const preferencesStore = usePreferencesStore()
	const show = ref(false)

	/**
	 *
	 */
	function loadPreferences() {
		preferencesStore.load()
		preferencesStore.getCalendars()
	}

	onMounted(() => {
		subscribe('polls:settings:show', () => {
			show.value = true
			loadPreferences()
		})
	})

	onUnmounted(() => {
		unsubscribe('polls:settings:show')
	})

</script>


<template>
	<NcAppSettingsDialog v-model:open="show" show-navigation>
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

