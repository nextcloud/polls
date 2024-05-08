<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
			<AdminPollsInNavigation />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Public poll registration dialog options')"
			:description="t('polls', 'These options regard the appearence of the registration dialog of public polls.')">
			<AdminSharePublicShowLogin />
			<AdminLegal />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Email options')"
			:description="t('polls', 'Add links to legal terms, if they exist and add an optional disclaimer to emails.')">
			<AdminEmail />
		</NcSettingsSection>

		<NcSettingsSection :name="t('polls', 'Job control')"
			:description="t('polls', 'Manually start backgropund jobs, independent from the cron schedule.')">
			<AdminJobs />
		</NcSettingsSection>
	</FlexSettings>
</template>

<script>
import {
	AdminActivities, AdminArchivePolls, AdminCombo, AdminEmail, AdminJobs, AdminLegal, 
	AdminPerformance, AdminPollCreation, AdminPollDownload, AdminPollsInNavigation,
	AdminShareOpenPoll, AdminSharePublicCreate, AdminSharePublicShowLogin,
	AdminShowMailAddresses
} from '../components/Settings/AdminSettings/index.js'
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
		AdminJobs,
		AdminLegal,
		AdminPerformance,
		AdminPollCreation,
		AdminPollDownload,
		AdminPollsInNavigation,
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
