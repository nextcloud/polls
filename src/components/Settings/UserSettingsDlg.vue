<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'

import CalendarSettings from './UserSettings/CalendarSettings.vue'
import FeatureSettings from './UserSettings/FeatureSettings.vue'
import StyleSettings from './UserSettings/StyleSettings.vue'

import { usePreferencesStore } from '../../stores/preferences'
import { Event } from '../../Types'

const preferencesStore = usePreferencesStore()
const show = ref(false)

/**
 *
 */
function loadPreferences(): void {
	preferencesStore.load()
	preferencesStore.getCalendars()
}

onMounted(() => {
	subscribe(Event.ShowSettings, () => {
		show.value = true
		loadPreferences()
	})
})

onUnmounted(() => {
	unsubscribe(Event.ShowSettings, () => {})
})
</script>

<template>
	<NcAppSettingsDialog v-model:open="show" show-navigation>
		<NcAppSettingsSection id="calendar" :name="t('polls', 'Calendar check')">
			<CalendarSettings />
		</NcAppSettingsSection>

		<NcAppSettingsSection
			id="div-settings"
			:name="t('polls', 'Personal preferences')">
			<FeatureSettings />
		</NcAppSettingsSection>

		<NcAppSettingsSection id="styles" :name="t('polls', 'Styles')">
			<StyleSettings />
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>
