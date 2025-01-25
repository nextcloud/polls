<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { onMounted } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

	import { FlexSettings } from '../components/Base/index.js'
	import {
		AdminActivities, AdminArchivePolls, AdminCombo, AdminEmail, AdminJobs, AdminLegal,
		AdminPerformance, AdminPollCreation, AdminPollDownload, AdminPollsInNavigation,
		AdminShareOpenPoll, AdminSharePublicCreate, AdminSharePublicShowLogin,
		AdminShowMailAddresses
	} from '../components/Settings/AdminSettings/index.js'
	import { useAppSettingsStore } from '../stores/appSettings.ts'
	import '../assets/scss/markdown.scss'

	const appSettingsStore = useAppSettingsStore()

	const sections = {
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


	onMounted(() => {
		appSettingsStore.load()
	})
</script>

<template>
	<FlexSettings>
		<NcSettingsSection v-bind="sections.pollSettings">
			<AdminPollCreation />
			<AdminPollDownload />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.shareSettings">
			<AdminShareOpenPoll />
			<AdminSharePublicCreate />
		</NcSettingsSection>

		<NcSettingsSection v-bind="sections.otherSettings">
			<AdminActivities />
			<AdminArchivePolls />
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
