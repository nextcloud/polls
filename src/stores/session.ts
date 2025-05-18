/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { getCurrentUser } from '@nextcloud/auth'
import { PublicAPI, SessionAPI } from '../Api/index.ts'
import { createDefault, User, AppPermissions } from '../Types/index.ts'
import { AppSettings, UpdateType } from './appSettings.ts'
import { usePreferencesStore, ViewMode, SessionSettings } from './preferences.ts'
import { FilterType } from './polls.ts'
import { Share } from './shares.ts'
import { RouteLocationNormalized, RouteRecordNameGeneric } from 'vue-router'
import { Logger } from '../helpers/index.ts'
import { usePollStore } from './poll.ts'
import { useOptionsStore } from './options.ts'
import { useVotesStore } from './votes.ts'
import { useCommentsStore } from './comments.ts'
import { useSubscriptionStore } from './subscription.ts'
import { AxiosError } from '@nextcloud/axios'

interface RouteParams {
	id: number
	token: string
	type: FilterType
}

export type Route = {
	currentRoute: string
	name: RouteRecordNameGeneric
	path: string
	params: RouteParams
}

export type UserStatus = {
	isLoggedin: boolean
	isAdmin: boolean
}

type Watcher = {
	id: string
}

export type Session = {
	appPermissions: AppPermissions
	appSettings: AppSettings
	currentUser: User
	route: Route
	sessionSettings: SessionSettings
	share: Share
	token: string | null
	userStatus: UserStatus
	watcher: Watcher
}

const MOBILE_BREAKPOINT = 480

export const useSessionStore = defineStore('session', {
	state: (): Session => ({
		appPermissions: {
			addShares: false,
			addSharesExternal: false,
			allAccess: false,
			changeForeignVotes: false,
			comboView: false,
			deanonymizePoll: false,
			pollCreation: false,
			pollDownload: false,
			publicShares: false,
			seeMailAddresses: false,
			unrestrictedOwner: false,
		},
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
			autoDelete: false,
			autoDeleteOffset: 30,
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
			useSiteLegalTerms: true,
			navigationPollsInList: true,
			finalPrivacyUrl: '',
			finalImprintUrl: '',
			comboGroups: [],
			publicSharesGroups: [],
			pollCreationGroups: [],
			pollDownloadGroups: [],
			showMailAddressesGroups: [],
			unrestrictedOwner: false,
			unrestrictedOwnerGroups: [],
			groups: [],
			status: {
				loadingGroups: false,
			},
		},
		route: {
			currentRoute: '',
			name: '',
			path: '',
			params: {
				id: 0,
				token: '',
				type: FilterType.Relevant,
			},
		},
		userStatus: {
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
		},
		watcher: {
			id: '',
		},
		token: null,
		currentUser: createDefault<User>(),
		share: createDefault<Share>(),
	}),

	getters: {
		publicToken(state): string {
			if (state.route.params.token) {
				return state.route.params.token as string
			}
			return ''
		},

		currentPollId(state): number {
			if (state.route.name === 'vote') {
				return state.route.params.id
			}
			return 0
		},

		viewTextPoll(state): ViewMode {
			const preferencesStore = usePreferencesStore()

			if (state.sessionSettings.manualViewTextPoll) {
				return state.sessionSettings.manualViewTextPoll
			}
			if (window.innerWidth > MOBILE_BREAKPOINT) {
				return preferencesStore.user.defaultViewTextPoll
			}
			return ViewMode.ListView
		},

		viewDatePoll(state): ViewMode {
			const preferencesStore = usePreferencesStore()
			if (state.sessionSettings.manualViewDatePoll) {
				return state.sessionSettings.manualViewDatePoll
			}
			if (window.innerWidth > MOBILE_BREAKPOINT) {
				return preferencesStore.user.defaultViewDatePoll
			}
			return ViewMode.ListView
		},
	},

	actions: {
		generateWatcherId() {
			this.watcher.id = Math.random().toString(36).substring(2)
		},
		async load(to: null | RouteLocationNormalized) {
			Logger.debug('Loading session')
			this.generateWatcherId()

			if (to !== null) {
				Logger.debug('Set requested route', { to })
				await this.setRouter(to)
				Logger.debug('Route set', { route: this.route })
			}

			try {
				const response = await (() => {
					if (this.route.name === 'publicVote') {
						return PublicAPI.getSession(this.publicToken)
					}
					return SessionAPI.getSession()
				})()

				this.$patch(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}

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

		async setRouter(payload: RouteLocationNormalized) {
			this.route.currentRoute = payload.fullPath
			this.route.name = payload.name
			this.route.path = payload.path
			this.route.params.id = payload.params.id as unknown as number
			this.route.params.token = payload.params.token as string
			this.route.params.type = payload.params.type as FilterType
		},

		// Share store
		async loadShare(): Promise<void> {
			if (this.route.name !== 'publicVote') {
				this.share = createDefault<Share>()
				return
			}

			try {
				const response = await PublicAPI.getShare(this.publicToken)
				this.share = response.data.share
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
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
				const response = await PublicAPI.setEmailAddress(
					this.publicToken,
					payload.emailAddress,
				)
				this.share = response.data.share
				pollStore.load()
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing email address', {
					error,
					payload,
				})
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
				const response = await PublicAPI.setDisplayName(
					this.publicToken,
					payload.displayName,
				)
				this.share = response.data.share
				pollStore.load()
				commentsStore.load()
				votesStore.load()
				optionsStore.load()
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error changing name', {
					error,
					payload,
				})
				throw error
			}
		},

		async deleteEmailAddress(): Promise<void> {
			const pollStore = usePollStore()
			const subscriptionStore = useSubscriptionStore()

			if (this.route.name !== 'publicVote') {
				return
			}

			try {
				const response = await PublicAPI.deleteEmailAddress(this.publicToken)
				this.share = response.data.share
				subscriptionStore.$state.subscribed = false
				subscriptionStore.write()
				pollStore.load()
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting email address', { error })
				throw error
			}
		},

		async resendInvitation() {
			if (this.route.name !== 'publicVote') {
				throw new Error('Not on public vote page')
			}

			try {
				return await PublicAPI.resendInvitation(this.publicToken)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error sending invitation', {
					error,
					token: this.route.params.token,
				})
				throw error
			}
		},
	},
})
