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
		<NcSettingsSection :name="t('polls', 'Poll settings')"
			:description="t('polls', 'Change poll settings globally (for all accounts)')">
			<AdminPollCreation />
			<AdminPollDownload />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Share settings')"
			:description="t('polls', 'Change share settings globally (for all accounts)')">
			<AdminShareOpenPoll />
			<AdminSharePublicCreate />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Other settings')"
			:description="t('polls', 'Enable or disable individual features.')">
			<AdminActivities />
			<AdminArchivePolls />
			<AdminCombo />
			<AdminShowMailAddresses />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Performance settings')"
			:description="t('polls', 'If you are experiencing connection problems, change how auto updates are retrieved.')">
			<AdminPerformance />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Public poll registration dialog options')"
			:description="t('polls', 'Options for the registration dialog of public polls.')">
			<AdminSharePublicShowLogin />
			<AdminLegal />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Email options')"
			:description="t('polls', 'Add links to legal terms, if they exist and add an optional disclaimer to emails.')">
			<AdminEmail />
		</NcSettingsSection>
	</FlexSettings>
</template>

<script>
import AdminActivities from '../components/Settings/AdminSettings/AdminActivities.vue'
import AdminArchivePolls from '../components/Settings/AdminSettings/AdminArchivePolls.vue'
import AdminCombo from '../components/Settings/AdminSettings/AdminCombo.vue'
import AdminEmail from '../components/Settings/AdminSettings/AdminEmail.vue'
import AdminLegal from '../components/Settings/AdminSettings/AdminLegal.vue'
import AdminPerformance from '../components/Settings/AdminSettings/AdminPerformance.vue'
import AdminPollCreation from '../components/Settings/AdminSettings/AdminPollCreation.vue'
import AdminPollDownload from '../components/Settings/AdminSettings/AdminPollDownload.vue'
import AdminShareOpenPoll from '../components/Settings/AdminSettings/AdminShareOpenPoll.vue'
import AdminSharePublicCreate from '../components/Settings/AdminSettings/AdminSharePublicCreate.vue'
import AdminSharePublicShowLogin from '../components/Settings/AdminSettings/AdminSharePublicShowLogin.vue'
import AdminShowMailAddresses from '../components/Settings/AdminSettings/AdminShowMailAddresses.vue'
import { FlexSettings } from '../components/Base/index.js'
import { NcSettingsSection } from '@nextcloud/vue'
import '../assets/scss/markdown.scss'

export default {
	name: 'AdminSettingsPage',

	components: {
		AdminActivities,
		AdminArchivePolls,
		AdminCombo,
		AdminEmail,
		AdminLegal,
		AdminPerformance,
		AdminPollCreation,
		AdminPollDownload,
		AdminShareOpenPoll,
		AdminSharePublicCreate,
		AdminSharePublicShowLogin,
		AdminShowMailAddresses,
		NcSettingsSection,
		FlexSettings,
	},

	created() {
		this.$store.dispatch('appSettings/get')
	},
}
</script>
