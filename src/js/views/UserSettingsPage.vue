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
	<div class="polls_user_settings">
		<SettingsSection :title="t('polls', 'Polls user settings')"
			:description="t('polls', 'Set your personal preferences for the polls app')">
			<FeatureSettings />
		</SettingsSection>

		<SettingsSection :title="t('polls', 'Performance settings')"
			:description="t('polls', 'Try to change these parameters to handle big polls')">
			<PerformanceSettings />
		</SettingsSection>

		<SettingsSection :title="t('polls', 'Experimental styles')"
			:description="t('polls', 'Some visual styling options.')">
			<StyleSettings />
		</SettingsSection>
	</div>
</template>

<script>

import { SettingsSection } from '@nextcloud/vue'
export default {
	name: 'UserSettingsPage',

	components: {
		SettingsSection,
		FeatureSettings: () => import('../components/Settings/UserSettings/FeatureSettings'),
		StyleSettings: () => import('../components/Settings/UserSettings/StyleSettings'),
		PerformanceSettings: () => import('../components/Settings/UserSettings/PerformanceSettings'),
	},

	created() {
		this.$store.dispatch('settings/getCalendars')
		this.$store.dispatch('settings/get')
	},
}
</script>

<style lang="scss">
.polls_user_settings {
	display: flex;
	flex-wrap: wrap;
	align-items: stretch;

	.settings-section {
		flex: 1 0 480px;
		margin-bottom: 0;
		border-bottom: 1px solid var(--color-border);
	}
}
</style>
