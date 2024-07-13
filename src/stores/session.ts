/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { getCurrentUser } from '@nextcloud/auth'
import { PublicAPI, SessionAPI } from '../Api/index.js'
import { User, AppPermissions, UserType } from '../Interfaces/interfaces.ts'
import { AppSettings } from './appSettings.ts'
import { usePreferencesStore } from './preferences.ts'
import { FilterType } from './polls.ts'
import { PollPermissions, usePollStore } from './poll.ts'
import { Share } from './share.ts'
import { RouteLocationNormalized } from 'vue-router'

enum ViewMode {
	TableView = 'table-view',
	ListView = 'list-view',
}

interface Router {
	currentRoute: string
	name: string
	path: string
	params: {
		id: number
		token: string
		type: FilterType
	}
}

export interface SessionSettings {
	manualViewDatePoll: '' | ViewMode
	manualViewTextPoll: '' | ViewMode
}

export interface UserStatus { 
	isLoggedin: boolean
	isAdmin: boolean
}

interface Session {
	token: string
	appPermissions: AppPermissions
	appSettings: AppSettings
	currentUser: User
	sessionSettings: SessionSettings
	viewModes: ViewMode[]
	router: Router
	userStatus: UserStatus
	share: Share | null
}

export const useSessionStore = defineStore('session', {
	state: (): Session => ({
		token: '',
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
		appPermissions: {
			allAccess: false,
			publicShares: false,
			pollCreation: false,
			seeMailAddresses: false,
			pollDownload: false,
		},
		viewModes: Object.values(ViewMode),
		sessionSettings: {
			manualViewDatePoll: '',
			manualViewTextPoll: '',
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
			groups: [],
			status: {
				loadingGroups: false
			}
		},
		router: {
			currentRoute: '',
			name: '',
			path: '',
			params: {
				id: 0,
				token: '',
				type: FilterType.Relevant,
			}
		},
		userStatus: {
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
		},
		share: null,
	}),
	getters: {
		viewTextPoll(state): ViewMode {
			const preferencesStore = usePreferencesStore()
			
			if (state.sessionSettings.manualViewTextPoll) {
				return state.sessionSettings.manualViewTextPoll
			}
			if (window.innerWidth > 480) {
				return preferencesStore.user.defaultViewTextPoll
			}
			return ViewMode.ListView
		},

		viewDatePoll(state): ViewMode {
			const preferencesStore = usePreferencesStore()
			if (state.sessionSettings.manualViewDatePoll) {
				return state.sessionSettings.manualViewDatePoll
			}
			if (window.innerWidth > 480) {
				return preferencesStore.user.defaultViewDatePoll
			}
			return ViewMode.ListView
		},
		
		pollPermissions(): PollPermissions {
			const pollStore = usePollStore()
			return pollStore.permissions
		}
	},

	actions: {
		async load() {
			let response = null

			try {
				if (this.router.name === 'publicVote') {
					response = await PublicAPI.getSession(this.router.params.token)
				} else {
					response = await SessionAPI.getSession()
				}
				this.$patch(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
	
				this.$reset()
				if (this.router.name === null) {
					this.$reset()
				} else {
					throw error
				}
			}
		},

		setViewDatePoll(viewMode: ViewMode) {
			this.sessionSettings.manualViewDatePoll = viewMode
		},

		setViewTextPoll(viewMode: ViewMode) {
			this.sessionSettings.manualViewTextPoll = viewMode
		},

		setRouter(payload: RouteLocationNormalized) {
			this.router.currentRoute = payload.fullPath
			this.router.name = payload.name
			this.router.path = payload.path
			this.router.params = payload.params
		},
	},
})
