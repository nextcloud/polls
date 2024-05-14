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
