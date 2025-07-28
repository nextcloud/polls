/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { RouteLocationNormalized } from 'vue-router'

import { getCurrentUser } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'

import { Logger } from '../helpers'
import { PublicAPI, SessionAPI } from '../Api'

import { useSubscriptionStore } from './subscription'
import { usePollGroupsStore } from './pollGroups'
import { usePreferencesStore } from './preferences'
import { usePollStore } from './poll'
import { usePollsStore } from './polls'

import { createDefault } from '../Types'

import type { AxiosError } from '@nextcloud/axios'
import type { ViewMode } from './preferences.types'
import type { Share } from './shares.types'
import type { PollType } from './poll.types'
import type { FilterType } from './polls.types'
import type { User } from '../Types'

import type { SessionStore } from './session.types'

const MOBILE_BREAKPOINT = 480

export const useSessionStore = defineStore('session', {
	state: (): SessionStore => ({
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
			viewModeDatePoll: '',
			viewModeTextPoll: '',
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
				type: 'relevant',
				slug: '',
			},
		},
		userStatus: {
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
		},
		watcher: {
			id: '',
			mode: 'noPolling',
			status: 'stopped',
			lastUpdate: Math.floor(Date.now() / 1000),
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

		viewModeTextPoll(state): ViewMode {
			if (state.sessionSettings.viewModeTextPoll) {
				return state.sessionSettings.viewModeTextPoll
			}

			const preferencesStore = usePreferencesStore()
			if (window.innerWidth > MOBILE_BREAKPOINT) {
				return preferencesStore.user.defaultViewTextPoll
			}
			return 'list-view'
		},

		viewModeDatePoll(state): ViewMode {
			if (state.sessionSettings.viewModeDatePoll) {
				return state.sessionSettings.viewModeDatePoll
			}

			const preferencesStore = usePreferencesStore()
			if (window.innerWidth > MOBILE_BREAKPOINT) {
				return preferencesStore.user.defaultViewDatePoll
			}
			return 'list-view'
		},

		windowTitle(state): string {
			const pollStore = usePollStore()

			const windowTitle = {
				prefix: `${t('polls', 'Polls')}`,
				name: 'Nextcloud',
			}

			if (state.route.name === 'list') {
				const pollsStore = usePollsStore()
				windowTitle.name =
					pollsStore.categories[
						this.route.params.type as FilterType
					].titleExt
			} else if (state.route.name === 'group') {
				const pollGroupsStore = usePollGroupsStore()
				windowTitle.name =
					pollGroupsStore.currentPollGroup?.titleExt
					|| pollGroupsStore.currentPollGroup?.name
					|| ''
			} else if (state.route.name === 'publicVote') {
				windowTitle.name = pollStore.configuration.title
			} else if (state.route.name === 'vote') {
				windowTitle.name =
					pollStore.configuration.title ?? t('polls', 'Enter title')
			}

			return `${windowTitle.prefix} – ${windowTitle.name}`
		},
	},

	actions: {
		getViewMode(pollType: PollType): ViewMode {
			if (pollType === 'datePoll') {
				return this.viewModeDatePoll
			}
			if (pollType === 'textPoll') {
				return this.viewModeTextPoll
			}
			return 'list-view'
		},

		setViewMode(pollType: PollType, viewMode: ViewMode): void {
			if (pollType === 'datePoll') {
				this.sessionSettings.viewModeDatePoll = viewMode
			}
			if (pollType === 'textPoll') {
				this.sessionSettings.viewModeTextPoll = viewMode
			}
		},

		generateWatcherId() {
			this.watcher.id = Math.random().toString(36).substring(2)
		},
		async load(
			to: null | RouteLocationNormalized,
			cheapLoading: boolean = false,
		) {
			Logger.debug('Loading session')
			this.generateWatcherId()

			if (to !== null) {
				Logger.debug('Set requested route', { to })
				await this.setRouter(to)
				Logger.debug('Route set', { route: this.route })
			}

			if (cheapLoading) {
				Logger.debug('Same route, skipping session load')
				return
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
			this.sessionSettings.viewModeDatePoll = viewMode
		},

		setViewTextPoll(viewMode: ViewMode) {
			this.sessionSettings.viewModeTextPoll = viewMode
		},

		async setRouter(payload: RouteLocationNormalized) {
			this.route.currentRoute = payload.fullPath
			this.route.name = payload.name
			this.route.path = payload.path
			this.route.params.id = payload.params.id as unknown as number
			this.route.params.token = payload.params.token as string
			this.route.params.type = payload.params.type as FilterType
			this.route.params.slug = payload.params.slug as string
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
