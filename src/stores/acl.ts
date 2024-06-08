/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, UserSettingsAPI } from '../Api/index.js'
import { User, AppPermissions } from '../Interfaces/interfaces.ts'
import { Logger } from '../helpers/index.js'
import { AppSettings } from './appSettings.ts'
import { useRouterStore } from './router.ts'

interface Acl {
	token: string,
	currentUser: User,
	appPermissions: AppPermissions
	appSettings: AppSettings
}

export const useAclStore = defineStore('acl', {
	state: (): Acl => ({
		token: '',
		currentUser: {
			userId: '',
			displayName: '',
			emailAddress: '',
			subName: '',
			subtitle: '',
			isNoUser: true,
			desc: '',
			type: 'user',
			id: '',
			user: '',
			organisation: '', 
			languageCode: '',
			localeCode: '',
			timeZone: '',
			icon: 'icon-user',
			categories: []
		  },
		appPermissions: {
			allAccess: false,
			publicShares: false,
			pollCreation: false,
			seeMailAddresses: false,
			pollDownload: false,
		},
		appSettings: {
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
		},
	}),

	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.getAcl(routerStore.params.token)
				} else {
					response = await UserSettingsAPI.getAcl()
				}
				
				this.$patch(response.data.acl)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
	
				this.$reset()
				if (routerStore.name === null) {
					// TODO: for some reason unauthorized users first get the root route resulting in a 401 
					// and after that the publicVote route is called as next route
					// therefore we just debug the error and reset the acl
	
					Logger.debug('getAcl failed', error)
					this.$reset()
				} else {
					throw error
				}
			}
		},
	},
})
