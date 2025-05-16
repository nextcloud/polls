/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { getCurrentUser } from '@nextcloud/auth'
import { PollsAPI, PublicAPI, SessionAPI } from '../Api/index.ts'
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
import { InvalidJSON } from '../Exceptions/Exceptions.ts'

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
	restart: boolean
	watching: boolean
	lastUpdated: number
	endPoint: string
	sleepTimeoutSeconds: number
	retryCounter: number
	blockWatch: boolean
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

const mobileBreakpoint = 480
const SLEEP_TIMEOUT_DEFAULT = 30
const MAX_TRIES = 5

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
			restart: false,
			watching: true,
			lastUpdated: Math.round(Date.now() / 1000),
			endPoint: '',
			sleepTimeoutSeconds: SLEEP_TIMEOUT_DEFAULT,
			retryCounter: 0,
			blockWatch: false,
		},
		token: null,
		currentUser: createDefault<User>(),
		share: createDefault<Share>(),
	}),

	getters: {
		watchEnabled(): boolean {
			return (
				!this.watcher.blockWatch
				&& this.appSettings.updateType !== UpdateType.NoPolling
				&& this.watcher.retryCounter < MAX_TRIES
			)
		},

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
		async watchPolls(): Promise<void> {
			this.watcher.retryCounter = 0

			while (this.watchEnabled) {
				try {
					const response = await this.fetchUpdates()

					if (
						response.headers['content-type'].includes('application/json')
					) {
						this.watcher.retryCounter = 0
						response.data.updates.forEach((item) => {
							this.watcher.lastUpdated = Math.max(
								item.updated,
								this.watcher.lastUpdated,
							)
						})
					} else {
						throw new InvalidJSON(
							`No JSON response recieved, got "${response.headers['content-type']}"`,
						)
					}
				} catch (error) {
					await this.handleConnectionException(error)
				}

				if (!this.watchEnabled) {
					return
				}

				// sleep if request was invalid or polling is set to "periodicPolling"
				if (this.watcher.retryCounter) {
					await this.sleep()
					Logger.debug(
						`Continue ${this.appSettings.updateType} after sleep`,
					)
				}

				// avoid requests when app is in background and pause
				while (document.hidden || !navigator.onLine) {
					if (navigator.onLine) {
						Logger.debug(
							`App in background, pause ${this.appSettings.updateType}`,
						)
					} else {
						Logger.debug(
							`Browser is offline, pause ${this.appSettings.updateType}`,
						)
					}
					await new Promise((resolve) => setTimeout(resolve, 5000))
					Logger.debug('Resume')
				}

				if (this.watcher.retryCounter) {
					Logger.debug(
						`Cancel watch after ${this.watcher.retryCounter} failed requests`,
					)
				}
			}
		},

		async fetchUpdates() {
			if (this.route.name === 'publicVote') {
				return PublicAPI.watchPoll(
					this.route.params.token,
					this.watcher.lastUpdated,
				)
			}
			return PollsAPI.watchPoll(this.route.params.id, this.watcher.lastUpdated)
		},

		sleep(): Promise<void> {
			const reason = this.watcher.retryCounter
				? `Connection error, Attempt:  ${this.watcher.retryCounter}/${MAX_TRIES})`
				: this.appSettings.updateType
			Logger.debug(
				`Sleep for ${this.watcher.sleepTimeoutSeconds} seconds (reason: ${reason})`,
			)
			return new Promise((resolve) =>
				setTimeout(resolve, this.watcher.sleepTimeoutSeconds * 1000),
			)
		},

		async handleConnectionException(e: unknown) {
			const error = e as AxiosError
			if (error.response?.status === 304) {
				// this is a wanted response, no updates where found.
				// resume to normal operation
				Logger.debug(`No updates - continue ${this.appSettings.updateType}`)
				this.watcher.retryCounter = 0
				return
			}
			if (error?.code === 'ERR_NETWORK') {
				Logger.debug(
					`Possibly offline - continue ${this.appSettings.updateType}`,
				)
				return
			}
			// Errors, which allow a retry. Increase counter and resume to normal operation

			this.watcher.retryCounter += 1

			if (error.response?.status === 503) {
				// Server possibly in maintenance mode
				this.watcher.sleepTimeoutSeconds =
					error.response?.headers['retry-after'] ?? SLEEP_TIMEOUT_DEFAULT
				Logger.debug(
					`Service not avaiable - retry ${this.appSettings.updateType} after ${this.watcher.sleepTimeoutSeconds} seconds`,
				)
				return
			}

			// Watch has to be canceled
			if (error?.code === 'ERR_CANCELED' || error?.code === 'ECONNABORTED') {
				Logger.debug('Watch canceled')
			} else {
				Logger.debug(
					`No response - ${this.appSettings.updateType} aborted - failed request ${this.watcher.retryCounter}/${MAX_TRIES}`,
					{ error },
				)
			}

			this.watcher.blockWatch = true
		},
		async load(to: null | RouteLocationNormalized) {
			Logger.debug('Loading session')

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
				this.watcher.blockWatch = false
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}

				this.$reset()
				if (this.route.name === null) {
					this.$reset()
				} else {
					this.watcher.blockWatch = true
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
