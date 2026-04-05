/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

import { getCurrentUser } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'

import { Logger } from '../helpers/modules/logger'
import { activeRoute } from '../router'
import { PublicAPI, SessionAPI } from '../Api'

import { useSubscriptionStore } from './subscription'
import { usePollGroupsStore } from './pollGroups'
import { usePreferencesStore } from './preferences'
import { usePollStore } from './poll'
import { usePollsStore } from './polls'

import { defaultUser } from '../Types'

import type { AxiosError } from '@nextcloud/axios'
import type { ViewMode } from './preferences.types'
import { defaultShare } from './shares.types'
import type { PollType } from './poll.types'
import type { FilterType } from './polls.types'

import type { SessionStore } from './session.types'
import { IANAZone } from 'luxon'

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
			viewModeForced: null,
			timezoneName: 'local',
		},
		appSettings: {
			finalImprintUrl: '',
			finalPrivacyUrl: '',
			navigationPollsInList: false,
			useLogin: false,
			useActivity: false,
			updateType: 'noPolling',
			currentVersion: '',
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
		currentUser: { ...defaultUser },
		share: { ...defaultShare, user: { ...defaultUser } },
		navigationStatus: 'idle',
	}),

	getters: {
		publicToken(): string {
			return activeRoute.value.params.token as string || ''
		},

		currentPollId(): number {
			if (activeRoute.value.meta.internalVotePage) {
				return Number(activeRoute.value.params.id)
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

		windowTitle(): string {
			const pollStore = usePollStore()
			const route = activeRoute.value

			const windowTitle = {
				prefix: `${t('polls', 'Polls')}`,
				name: 'Nextcloud',
			}

			if (route.meta.listPage && route.params.type) {
				const pollsStore = usePollsStore()
				windowTitle.name =
					pollsStore.categories[
						route.params.type as FilterType
					].titleExt
			} else if (route.meta.groupPage) {
				const pollGroupsStore = usePollGroupsStore()
				windowTitle.name =
					pollGroupsStore.currentPollGroup?.titleExt
					|| pollGroupsStore.currentPollGroup?.name
					|| ''
			} else if (route.meta.publicVotePage) {
				windowTitle.name = pollStore.configuration.title
			} else if (route.meta.votePage) {
				windowTitle.name =
					pollStore.configuration.title ?? t('polls', 'Enter title')
			}

			return `${windowTitle.prefix} – ${windowTitle.name}`
		},

		userTimezoneName(): string {
			return (
				this.currentUser.timeZone
				|| Intl.DateTimeFormat().resolvedOptions().timeZone
			)
		},

		pollTimezoneName(): string {
			const pollStore = usePollStore()
			return (
				pollStore.configuration.timezoneName
				|| this.currentUser.timeZone
				|| Intl.DateTimeFormat().resolvedOptions().timeZone
			)
		},

		currentTimezoneName(): string {
			if (this.sessionSettings.timezoneName === 'local') {
				return this.userTimezoneName
			}
			if (this.sessionSettings.timezoneName === 'poll') {
				return this.pollTimezoneName
			}
			if (IANAZone.isValidZone(this.sessionSettings.timezoneName)) {
				return this.sessionSettings.timezoneName
			}
			return this.userTimezoneName
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

		setViewMode(
			viewMode: ViewMode | 'user-pref',
			pollType: PollType | null = null,
		): void {
			if (viewMode === 'user-pref') {
				this.sessionSettings.viewModeForced = null
				return
			}
			if (pollType === null) {
				this.sessionSettings.viewModeForced = viewMode
				return
			}

			if (pollType === 'datePoll') {
				this.sessionSettings.viewModeForced = null
				this.sessionSettings.viewModeDatePoll = viewMode
				return
			}
			if (pollType === 'textPoll') {
				this.sessionSettings.viewModeForced = null
				this.sessionSettings.viewModeTextPoll = viewMode
			}
		},

		generateWatcherId() {
			this.watcher.id = Math.random().toString(36).substring(2)
		},

		async loadSession() {
			Logger.debug('Loading session')
			this.generateWatcherId()

			try {
				const response = await (() => {
					if (activeRoute.value.meta.publicPage) {
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
				throw error
			}
			Logger.debug('Session loaded')
		},

		setViewDatePoll(viewMode: ViewMode) {
			this.sessionSettings.viewModeDatePoll = viewMode
		},

		setViewTextPoll(viewMode: ViewMode) {
			this.sessionSettings.viewModeTextPoll = viewMode
		},

		loadAppSettings(): void {},

		async updateEmailAddress(payload: { emailAddress: string }): Promise<void> {
			const pollStore = usePollStore()

			if (!activeRoute.value.meta.publicVotePage) {
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

			if (!activeRoute.value.meta.publicVotePage) {
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

			if (!activeRoute.value.meta.publicVotePage) {
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
			if (!activeRoute.value.meta.publicVotePage) {
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
					token: this.publicToken,
				})
				throw error
			}
		},
	},
})
