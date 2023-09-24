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
		<NcSettingsSection :title="t('polls', 'Share restrictions')"
			:description="t('polls', 'Restrict the share actions globally or on a group base')">
			<AdminShareSettings />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Poll creation restrictions')"
			:description="t('polls', 'Restrict the creation of new polls globally or on a group base')">
			<AdminPollCreation />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Legal terms for public poll registration')"
			:description="t('polls', 'Override the default links of your site to your legal terms. Leave empty to use the links, which are configured in the theming app.')">
			<AdminLegal />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Email options')"
			:description="t('polls', 'Add links to legal terms, if they exist and add an optional disclaimer to emails.')">
			<AdminEmail />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Other settings')"
			:description="t('polls', 'Activate or disable individual features.')">
			<AdminMisc />
			<AdminCombo />
			<AdminPollDownload />
			<AdminHideMailAddresses />
		</NcSettingsSection>

		<NcSettingsSection :title="t('polls', 'Performance settings')"
			:description="t('polls', 'If you are experiencing connection problems, change how auto updates are retrieved.')">
			<AdminPerformance />
		</NcSettingsSection>
	</FlexSettings>
</template>

<script>
import AdminCombo from '../components/Settings/AdminSettings/AdminCombo.vue'
import AdminEmail from '../components/Settings/AdminSettings/AdminEmail.vue'
import AdminLegal from '../components/Settings/AdminSettings/AdminLegal.vue'
import AdminMisc from '../components/Settings/AdminSettings/AdminMisc.vue'
import AdminPerformance from '../components/Settings/AdminSettings/AdminPerformance.vue'
import AdminPollCreation from '../components/Settings/AdminSettings/AdminPollCreation.vue'
import AdminPollDownload from '../components/Settings/AdminSettings/AdminPollDownload.vue'
import AdminShareSettings from '../components/Settings/AdminSettings/AdminShareSettings.vue'
import AdminHideMailAddresses from '../components/Settings/AdminSettings/AdminHideMailAddresses.vue'
import { FlexSettings } from '../components/Base/index.js'
import { NcSettingsSection } from '@nextcloud/vue'
import '../assets/scss/markdown.scss'

export default {
	name: 'AdminSettingsPage',

	components: {
		AdminCombo,
		AdminEmail,
		AdminLegal,
		AdminMisc,
		AdminPerformance,
		AdminPollCreation,
		AdminPollDownload,
		AdminShareSettings,
		AdminHideMailAddresses,
		NcSettingsSection,
		FlexSettings,
	},

	created() {
		this.$store.dispatch('appSettings/get')
	},
}
</script>
