<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { t } from '@nextcloud/l10n'

import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

import FlexSettings from '../components/Base/modules/FlexSettings.vue'

import AdminActivities from '../components/Settings/AdminSettings/AdminActivities.vue'
import AdminArchivePolls from '../components/Settings/AdminSettings/AdminArchivePolls.vue'
import AdminCombo from '../components/Settings/AdminSettings/AdminCombo.vue'
import AdminDeletePolls from '../components/Settings/AdminSettings/AdminDeletePolls.vue'
import AdminEmail from '../components/Settings/AdminSettings/AdminEmail.vue'
import AdminJobs from '../components/Settings/AdminSettings/AdminJobs.vue'
import AdminLegal from '../components/Settings/AdminSettings/AdminLegal.vue'
import AdminPerformance from '../components/Settings/AdminSettings/AdminPerformance.vue'
import AdminPollCreation from '../components/Settings/AdminSettings/AdminPollCreation.vue'
import AdminPollDownload from '../components/Settings/AdminSettings/AdminPollDownload.vue'
import AdminPollsInNavigation from '../components/Settings/AdminSettings/AdminPollsInNavigation.vue'
import AdminShareOpenPoll from '../components/Settings/AdminSettings/AdminShareOpenPoll.vue'
import AdminSharePublicCreate from '../components/Settings/AdminSettings/AdminSharePublicCreate.vue'
import AdminSharePublicShowLogin from '../components/Settings/AdminSettings/AdminSharePublicShowLogin.vue'
import AdminShowMailAddresses from '../components/Settings/AdminSettings/AdminShowMailAddresses.vue'
import AdminUnrescrictedOwners from '../components/Settings/AdminSettings/AdminUnrescrictedOwners.vue'

import { useAppSettingsStore } from '../stores/appSettings.ts'
import '../assets/scss/markdown.scss'

const appSettingsStore = useAppSettingsStore()

const sections = {
	pollSettings: {
		name: t('polls', 'Poll settings'),
		description: t('polls', 'Change poll settings globally (for all accounts)'),
	},
	shareSettings: {
		name: t('polls', 'Share settings'),
		description: t('polls', 'Change share settings globally (for all accounts)'),
	},
	otherSettings: {
		name: t('polls', 'Other settings'),
		description: t('polls', 'Enable or disable individual features.'),
	},
	performanceSettings: {
		name: t('polls', 'Performance settings'),
		description: t(
			'polls',
			'If you are experiencing connection problems, change how auto updates are retrieved.',
		),
	},
	publicSettings: {
		name: t('polls', 'Public poll registration dialog options'),
		description: t(
			'polls',
			'These options regard the appearence of the registration dialog of public polls.',
		),
	},
	emailSettings: {
		name: t('polls', 'Email options'),
		description: t(
			'polls',
			'Add links to legal terms, if they exist and add an optional disclaimer to emails.',
		),
	},
	jobSettings: {
		name: t('polls', 'Job control'),
		description: t(
			'polls',
			'Manually start backgropund jobs, independent from the cron schedule.',
		),
	},
}

onMounted(() => {
	appSettingsStore.load()
})
</script>

<template>
	<FlexSettings>
		<NcSettingsSection v-bind="sections.pollSettings">
			<AdminPollCreation />
			<AdminPollDownload />
			<AdminUnrescrictedOwners />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.shareSettings">
			<AdminShareOpenPoll />
			<AdminSharePublicCreate />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.otherSettings">
			<AdminActivities />
			<AdminArchivePolls />
			<AdminDeletePolls />
			<AdminCombo />
			<AdminShowMailAddresses />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.performanceSettings">
			<AdminPerformance />
			<AdminPollsInNavigation />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.publicSettings">
			<AdminSharePublicShowLogin />
			<AdminLegal />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.emailSettings">
			<AdminEmail />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.jobSettings">
			<AdminJobs />
		</NcSettingsSection>
	</FlexSettings>
</template>
