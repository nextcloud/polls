/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { getCurrentUser } from '@nextcloud/auth'
import { PublicAPI, SessionAPI } from '../Api/index.js'
import { User, AppPermissions, UserType } from '../Types/index.ts'
import { AppSettings, UpdateType } from './appSettings.ts'
import { usePreferencesStore, ViewMode, SessionSettings } from './preferences.ts'
import { FilterType } from './polls.ts'
import { Share } from './share.ts'
import { RouteLocationNormalized } from 'vue-router'

export type Route = {
	currentRoute: string
	name: string
	path: string
	params: {
		id: number
		token: string
		type: FilterType
	}
}

export type UserStatus = { 
	isLoggedin: boolean
	isAdmin: boolean
}

export type Session = {
	token: string
	appPermissions: AppPermissions
	appSettings: AppSettings
	currentUser: User
	sessionSettings: SessionSettings
	viewModes: ViewMode[]
	route: Route
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
			comboView: false,
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
			updateType: UpdateType.NoPolling,
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
		route: {
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
	},

	actions: {
		async load() {
			let response = null
			try {
				if (this.route.name === 'publicVote') {
					response = await PublicAPI.getSession(this.router.params.token)
				} else {
					response = await SessionAPI.getSession()
				}
				this.$patch(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
	
				this.$reset()
				if (this.route.name === null) {
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
			this.route.currentRoute = payload.fullPath
			this.route.name = payload.name
			this.route.path = payload.path
			this.route.params = payload.params
		},
	},
})
