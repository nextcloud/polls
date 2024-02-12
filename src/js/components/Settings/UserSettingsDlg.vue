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
	<NcAppSettingsDialog v-model:open="show" :show-navigation="true">
		<NcAppSettingsSection id="calendar" :name="t('polls', 'Calendar check')">
			<CalendarSettings />
		</NcAppSettingsSection>

		<NcAppSettingsSection id="div-settings" :name="t('polls', 'User settings')">
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

export default {
	name: 'UserSettingsDlg',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		CalendarSettings: () => import('./UserSettings/CalendarSettings.vue'),
		FeatureSettings: () => import('./UserSettings/FeatureSettings.vue'),
		StyleSettings: () => import('./UserSettings/StyleSettings.vue'),
		PerformanceSettings: () => import('./UserSettings/PerformanceSettings.vue'),
	},

	data() {
		return {
			show: false,
		}
	},

	watch: {
		async show() {
			if (this.show === true) {
				this.$store.dispatch('settings/get')
				this.$store.dispatch('settings/getCalendars')
			}
		},
	},

	created() {
		subscribe('polls:settings:show', () => {
			this.show = true
		})

	},

	beforeUnmount() {
		unsubscribe('polls:settings:show')
	},
}
</script>
