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
		<NcSettingsSection :title="t('polls', 'Calendar check')"
			:description="t('polls', 'Search for conflicting calendar entries')">
			<CalendarSettings />
		</NcSettingsSection>
		<NcSettingsSection :title="t('polls', 'Polls user settings')"
			:description="t('polls', 'Set your personal preferences for the polls app')">
			<FeatureSettings />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Performance settings')"
			:description="t('polls', 'Try to change these parameters to handle big polls')">
			<PerformanceSettings />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Experimental styles')"
			:description="t('polls', 'Some visual styling options.')">
			<StyleSettings />
		</NcSettingsSection>
	</div>
</template>

<script>

import { defineAsyncComponent } from 'vue'
import { NcSettingsSection } from '@nextcloud/vue'
export default {
	name: 'UserSettingsPage',

	components: {
		NcSettingsSection,
		CalendarSettings: defineAsyncComponent(() => import('../components/Settings/UserSettings/CalendarSettings.vue')),
		FeatureSettings: defineAsyncComponent(() => import('../components/Settings/UserSettings/FeatureSettings.vue')),
		StyleSettings: defineAsyncComponent(() => import('../components/Settings/UserSettings/StyleSettings.vue')),
		PerformanceSettings: defineAsyncComponent(() => import('../components/Settings/UserSettings/PerformanceSettings.vue')),
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

.user_settings {
	padding-top: 16px;

	textarea {
		width: 99%;
		resize: vertical;
		height: 230px;
	}
}

.settings_details {
	padding-bottom: 16px;
	margin-left: 36px;
}

</style>
