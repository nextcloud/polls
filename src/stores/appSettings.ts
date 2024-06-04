/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { AppSettingsAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'

export interface AppSettings {
	allAccessGroups: string[]
	allowCombo: boolean
	allowPublicShares: boolean
	allowAllAccess: boolean
	allowPollCreation: boolean
	allowPollDownload: boolean
	autoArchive: boolean
	autoArchiveOffset: number
	defaultPrivacyUrl: string
	defaultImprintUrl: string
	disclaimer: string
	imprintUrl: string
	legalTermsInEmail: boolean
	privacyUrl: string
	showMailAddresses: boolean
	showLogin: boolean
	updateType: string
	useActivity: boolean
	useCollaboration: boolean
	navigationPollsInList: boolean
	usePrivacyUrl: string
	useImprintUrl: string
	comboGroups: string[]
	publicSharesGroups: string[]
	pollCreationGroups: string[]
	pollDownloadGroups: string[]
	showMailAddressesGroups: string[]
}

export const useAclStore = defineStore('acl', {
	state: (): AppSettings => ({
		allAccessGroups: [],
		allowCombo: true,
		allowPublicShares: true,
		allowAllAccess: true,
		allowPollCreation: true,
		allowPollDownload: true,
		autoArchive: false,
		autoArchiveOffset: 30,
		defaultPrivacyUrl: '',
		defaultImprintUrl: '',
		disclaimer: '',
		imprintUrl: '',
		legalTermsInEmail: false,
		privacyUrl: '',
		showMailAddresses: false,
		showLogin: true,
		updateType: 'noPolling',
		useActivity: false,
		useCollaboration: true,
		navigationPollsInList: true,
		usePrivacyUrl: '',
		useImprintUrl: '',
		comboGroups: [],
		publicSharesGroups: [],
		pollCreationGroups: [],
		pollDownloadGroups: [],
		showMailAddressesGroups: [],
	}),

	actions: {
		async load() {
			try {
				const response = await AppSettingsAPI.getAppSettings()
				this.$patch(response.data.appSettings)
			} catch (error) {
				Logger.debug('Error getting appSettings', { error })
			}
		},
	
		async write() {
			try {
				const response = await AppSettingsAPI.writeAppSettings(this.$state)
				this.$patch(response.data.appSettings)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing appSettings', { error, appSettings: this.$state })
				throw error
			}
		},
	},
})
