/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { AppSettingsAPI } from '../Api/index.ts'
import { Logger } from '../helpers/index.ts'
import { AxiosError } from '@nextcloud/axios'

export enum UpdateType {
	NoPolling = 'noPolling',
	Periodic = 'periodicPolling',
	LongPolling = 'longPolling',
}
export type Group = {
	id: string
	userId: string
	displayName: string
	emailAddress: string
	isNoUser: boolean
	type: string
}

export type AppSettings = {
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
	unrestrictedOwner: boolean
	updateType: UpdateType
	useActivity: boolean
	useCollaboration: boolean
	navigationPollsInList: boolean
	finalPrivacyUrl: string
	finalImprintUrl: string
	comboGroups: string[]
	publicSharesGroups: string[]
	pollCreationGroups: string[]
	pollDownloadGroups: string[]
	showMailAddressesGroups: string[]
	unrestrictedOwnerGroups: string[]
	groups: Group[]
	status: {
		loadingGroups: boolean
	}
}

export const useAppSettingsStore = defineStore('appSettings', {
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
		unrestrictedOwner: false,
		updateType: UpdateType.NoPolling,
		useActivity: false,
		useCollaboration: true,
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
