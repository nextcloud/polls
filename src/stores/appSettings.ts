/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

import { AppSettingsAPI } from '../Api'
import { Logger } from '../helpers/modules/logger'

import type { AxiosError } from '@nextcloud/axios'
import type { AppSettingsStore } from './appSettings.types'

export const useAppSettingsStore = defineStore('appSettings', {
	state: (): AppSettingsStore => ({
		allAccessGroups: [],
		allowCombo: true,
		allowPublicShares: true,
		allowAllAccess: true,
		allowPollCreation: true,
		allowPollDownload: true,
		autoArchive: false,
		autoDelete: false,
		autoArchiveOffset: 30,
		autoDeleteOffset: 30,
		defaultPrivacyUrl: '',
		defaultImprintUrl: '',
		disclaimer: '',
		imprintUrl: '',
		legalTermsInEmail: false,
		privacyUrl: '',
		showMailAddresses: false,
		showLogin: true,
		unrestrictedOwner: false,
		updateType: 'noPolling',
		useActivity: false,
		useCollaboration: true,
		useSiteLegalTerms: true,
		navigationPollsInList: true,
		finalPrivacyUrl: '',
		finalImprintUrl: '',
		comboGroups: [],
		publicSharesGroups: [],
		pollCreationGroups: [],
		pollDownloadGroups: [],
		showMailAddressesGroups: [],
		unrestrictedOwnerGroups: [],
		groups: [],
		status: {
			loadingGroups: false,
		},
	}),

	actions: {
		async load(): Promise<void> {
			try {
				const response = await AppSettingsAPI.getAppSettings()
				this.$patch(response.data.appSettings)
			} catch (error) {
				Logger.error('Error getting appSettings', { error })
			}
		},

		async write(): Promise<void> {
			try {
				const response = await AppSettingsAPI.writeAppSettings(this.$state)
				this.$patch(response.data.appSettings)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing appSettings', {
					error,
					appSettings: this.$state,
				})
				throw error
			}
		},

		loadGroups(query: string): void {
			const debouncedLoad = this.$debounce(async () => {
				this.status.loadingGroups = true

				try {
					const response = await AppSettingsAPI.getGroups(query)
					this.groups = response.data.groups
					this.status.loadingGroups = false
				} catch (error) {
					if ((error as AxiosError)?.code === 'ERR_CANCELED') {
						return
					}
					Logger.error('Error getting groups', { error })
					this.status.loadingGroups = false
				}
			}, 500)

			debouncedLoad()
		},
	},
})
