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
	<AppSettingsDialog :open.sync="show" :show-navigation="true">
		<AppSettingsSection :title="t('polls', 'User settings')">
			<FeatureSettings />
		</AppSettingsSection>

		<AppSettingsSection :title="t('polls', 'Performance settings')">
			<PerformanceSettings />
		</AppSettingsSection>

		<AppSettingsSection :title="t('polls', 'Styles')">
			<StyleSettings />
		</AppSettingsSection>
	</AppSettingsDialog>
</template>

<script>

import { AppSettingsDialog, AppSettingsSection } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

export default {
	name: 'UserSettingsDlg',

	components: {
		AppSettingsDialog,
		AppSettingsSection,
		FeatureSettings: () => import('./UserSettings/FeatureSettings'),
		StyleSettings: () => import('./UserSettings/StyleSettings'),
		PerformanceSettings: () => import('./UserSettings/PerformanceSettings'),
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

	beforeDestroy() {
		unsubscribe('polls:settings:show')
	},
}
</script>
