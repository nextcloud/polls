/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, UserSettingsAPI } from '../Api/index.js'
import { User, AppPermissions, UserType } from '../Interfaces/interfaces.ts'
import { Logger } from '../helpers/index.js'
import { AppSettings } from './appSettings.ts'
import { useRouterStore } from './router.ts'

enum ViewMode {
	TableView = 'table-view',
	ListView = 'list-view',
}

interface UserPreferences {
	useCommentsAlternativeStyling: boolean
	useAlternativeStyling: boolean
	calendarPeek: boolean
	checkCalendars: [],
	checkCalendarsBefore: number,
	checkCalendarsAfter: number,
	defaultViewTextPoll: ViewMode
	defaultViewDatePoll: ViewMode
	performanceThreshold: number,
	pollCombo: number[],
	relevantOffset: number,
}

export interface SessionSettings {
	manualViewDatePoll: '' | ViewMode
	manualViewTextPoll: '' | ViewMode
}

interface Session {
	token: string
	appPermissions: AppPermissions
	appSettings: AppSettings
	currentUser: User
	preferences: UserPreferences
	params: SessionSettings
	viewModes: ViewMode[]
}

export const useSessionStore = defineStore('session', {
	state: (): Session => ({
		viewModes: Object.values(ViewMode),
		params: {
			manualViewDatePoll: '',
			manualViewTextPoll: '',
		},
		preferences: {
			useCommentsAlternativeStyling: false,
			useAlternativeStyling: false,
			calendarPeek: false,
			checkCalendars: [],
			checkCalendarsBefore: 0,
			checkCalendarsAfter: 0,
			defaultViewTextPoll: ViewMode.TableView,
			defaultViewDatePoll: ViewMode.TableView,
			performanceThreshold: 1000,
			pollCombo: [],
			relevantOffset: 30,
		},
		currentUser: {
			userId: '',
			displayName: '',
			emailAddress: '',
			subName: '',
			subtitle: '',
			isNoUser: true,
			desc: '',
			type: UserType.User,
			id: '',
			user: '',
			organisation: '', 
			languageCode: '',
			localeCode: '',
			timeZone: '',
			categories: []
		},
		token: '',
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
		}
	}),

	actions: {
		async load() {
			const routerStore = useRouterStore()
			const response = {
				acl: null,
				preferences: null,
			}

			try {
				if (routerStore.name === 'publicVote') {
					response.acl = await PublicAPI.getAcl(routerStore.params.token)
				} else {
					response.acl = await UserSettingsAPI.getAcl()
					response.preferences = await UserSettingsAPI.getUserSettings()
					Logger.debug('getAcl response', response.acl.data)
				}
				this.$patch(response.acl.data.acl)
				this.$patch({ preferences: response.preferences.data.preferences })
				Logger.debug('Acl loaded', this.$state)
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
