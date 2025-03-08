/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { getCurrentUser } from '@nextcloud/auth'
import { PublicAPI, SessionAPI } from '../Api/index.js'
import { User, AppPermissions } from '../Types/index.ts'
import { AppSettings, UpdateType } from './appSettings.ts'
import { usePreferencesStore, ViewMode, SessionSettings } from './preferences.ts'
import { FilterType } from './polls.ts'
import { Share } from './shares.ts'
import { RouteLocationNormalized } from 'vue-router'
import { Logger } from '../helpers/index.ts'
import { usePollStore } from './poll.ts'
import { useOptionsStore } from './options.ts'
import { useVotesStore } from './votes.ts'
import { useCommentsStore } from './comments.ts'
import { useSubscriptionStore } from './subscription.ts'

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
	token: string | null
	appPermissions: AppPermissions
	appSettings: AppSettings
	currentUser: User |null
	sessionSettings: SessionSettings
	viewModes: ViewMode[]
	route: Route
	userStatus: UserStatus
	share: Share | null
}

const mobileBreakpoint = 480
export const useSessionStore = defineStore('session', {
	state: (): Session => ({
		appPermissions: {
			addShares: false,
			addSharesExternal: false,
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
			finalPrivacyUrl: '',
			finalImprintUrl: '',
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
		token: null,
		currentUser: null,
		share: null,
	}),

	getters: {
		publicToken(state): string {
			if (state.route.params.token) {
				return state.route.params.token
			}
			return ''
		},

		viewTextPoll(state): ViewMode {
			const preferencesStore = usePreferencesStore()

			if (state.sessionSettings.manualViewTextPoll) {
				return state.sessionSettings.manualViewTextPoll
			}
			if (window.innerWidth > mobileBreakpoint) {
				return preferencesStore.user.defaultViewTextPoll
			}
			return ViewMode.ListView
		},

		viewDatePoll(state): ViewMode {
			const preferencesStore = usePreferencesStore()
			if (state.sessionSettings.manualViewDatePoll) {
				return state.sessionSettings.manualViewDatePoll
			}
			if (window.innerWidth > mobileBreakpoint) {
				return preferencesStore.user.defaultViewDatePoll
			}
			return ViewMode.ListView
		},
	},

	actions: {
		async load() {
			Logger.debug('Loading session')
			let response = null
			try {
				if (this.route.name === 'publicVote') {
					response = await PublicAPI.getSession(this.route.params.token)
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
			Logger.debug('Session loaded')
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

		// Share store
		async loadShare(): Promise<void> {
			if (this.route.name !== 'publicVote') {
				this.share = null
				return
			}

			try {
				const response = await PublicAPI.getShare(this.route.params.token)
				this.share = response.data.share
				return response.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error retrieving share', { error })
				throw error
			}
		},

		async updateEmailAddress(payload: { emailAddress: string }): Promise<void> {
			const pollStore = usePollStore()

			if (this.route.name !== 'publicVote') {
				return
			}

			try {
				const response = await PublicAPI.setEmailAddress(this.route.params.token, payload.emailAddress)
				this.share = response.data.share
				pollStore.load()

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error, payload })
				throw error
			}
		},

		async updateDisplayName(payload: { displayName: string }): Promise<void> {
			const pollStore = usePollStore()
			const commentsStore = useCommentsStore()
			const votesStore = useVotesStore()
			const optionsStore = useOptionsStore()

			if (this.route.name !== 'publicVote') {
				return
			}

			try {
				const response = await PublicAPI.setDisplayName(this.route.params.token, payload.displayName)
				this.share = response.data.share
				pollStore.load()
				commentsStore.load()
				votesStore.load()
				optionsStore.load()

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error changing name', { error, payload })
				throw error
			}
		},

		async deleteEmailAddress(): Promise<void>{
			const pollStore = usePollStore()
			const subscriptionStore = useSubscriptionStore()

			if (this.route.name !== 'publicVote') {
				return
			}

			try {
				const response = await PublicAPI.deleteEmailAddress(this.route.params.token)
				this.share = response.data.share
				subscriptionStore.$state.subscribed = false
				subscriptionStore.write()
				pollStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error })
				throw error
			}
		},

		async resendInvitation() {
			if (this.route.name !== 'publicVote') {
				return
			}

			try {
				return await PublicAPI.resendInvitation(this.route.params.token)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error sending invitation', { error, token: this.route.params.token })
				throw error
			}
		},
	},
})
