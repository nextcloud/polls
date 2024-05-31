<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<FlexSettings>
		<NcSettingsSection v-bind="pollSettings">
			<AdminPollCreation />
			<AdminPollDownload />
		</NcSettingsSection>

		<NcSettingsSection v-bind="shareSettings">
			<AdminShareOpenPoll />
			<AdminSharePublicCreate />
		</NcSettingsSection>

		<NcSettingsSection v-bind="otherSettings">
			<AdminActivities />
			<AdminArchivePolls />
			<AdminCombo />
			<AdminShowMailAddresses />
		</NcSettingsSection>

		<NcSettingsSection v-bind="performanceSettings">
			<AdminPerformance />
			<AdminPollsInNavigation />
		</NcSettingsSection>

		<NcSettingsSection v-bind="publicSettings">
			<AdminSharePublicShowLogin />
			<AdminLegal />
		</NcSettingsSection>

		<NcSettingsSection v-bind="emailSettings">
			<AdminEmail />
		</NcSettingsSection>

		<NcSettingsSection v-bind="jobSettings">
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
import { t } from '@nextcloud/l10n'

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

	data() {
		return {
		pollSettings: {
			name: t('polls', 'Poll settings'),
			description: t('polls', 'Change poll settings globally (for all accounts)')
		},
		shareSettings: {
			name: t('polls', 'Share settings'),
			description: t('polls', 'Change share settings globally (for all accounts)')
		},
		otherSettings: {
			name: t('polls', 'Other settings'),
			description: t('polls', 'Enable or disable individual features.')
		},
		performanceSettings: {
			name: t('polls', 'Performance settings'),
			description: t('polls', 'If you are experiencing connection problems, change how auto updates are retrieved.')
		},
		publicSettings: {
			name: t('polls', 'Public poll registration dialog options'),
			description: t('polls', 'These options regard the appearence of the registration dialog of public polls.')
		},
		emailSettings: {
			name: t('polls', 'Email options'),
			description: t('polls', 'Add links to legal terms, if they exist and add an optional disclaimer to emails.')
		},
		jobSettings: {
			name: t('polls', 'Job control'),
			description: t('polls', 'Manually start backgropund jobs, independent from the cron schedule.')
		}
		}
	},

	created() {
		this.$store.dispatch('appSettings/get')
	},
}
</script>
