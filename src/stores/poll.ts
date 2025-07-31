/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
// eslint-disable-next-line import/no-named-as-default
import DOMPurify from 'dompurify'
import { marked } from 'marked'
import { gfmHeadingId } from 'marked-gfm-heading-id'

import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import { Logger } from '../helpers'
import { PublicAPI, PollsAPI } from '../Api'
import { createDefault, Event } from '../Types'

import { useVotesStore } from './votes'
import { useOptionsStore } from './options'
import { usePollsStore } from './polls'
import { useSessionStore } from './session'
import { useSubscriptionStore } from './subscription'
import { useCommentsStore } from './comments'

import type { AxiosError } from '@nextcloud/axios'
import type { User } from '../Types'
import type {
	Poll,
	PollType,
	AllowProposals,
	PollStore,
	PollTypesType,
} from './poll.types'
import type { ViewMode } from './preferences.types'

const markedPrefix = {
	prefix: 'desc-',
}

export const pollTypes: Record<PollType, PollTypesType> = {
	textPoll: {
		name: t('polls', 'Text poll'),
	},
	datePoll: {
		name: t('polls', 'Date poll'),
	},
}

export const usePollStore = defineStore('poll', {
	state: (): PollStore => ({
		id: 0,
		type: 'datePoll',
		voteVariant: 'simple',
		descriptionSafe: '',
		configuration: {
			title: '',
			description: '',
			access: 'private',
			allowComment: false,
			allowMaybe: false,
			allowProposals: 'disallow',
			anonymous: false,
			autoReminder: false,
			collapseDescription: true,
			expire: 0,
			forceConfidentialComments: false,
			hideBookedUp: false,
			proposalsExpire: 0,
			showResults: 'always',
			useNo: true,
			maxVotesPerOption: 0,
			maxVotesPerUser: 0,
		},
		owner: createDefault<User>(),
		pollGroups: [],
		status: {
			anonymizeLevel: 'ANON_NONE',
			lastInteraction: 0,
			created: 0,
			isAnonymous: false,
			isArchived: false,
			isExpired: false,
			isRealAnonymous: false,
			relevantThreshold: 0,
			deletionDate: 0,
			archivedDate: 0,
			countParticipants: 0,
		},
		currentUserStatus: {
			groupInvitations: [],
			isInvolved: false,
			isLocked: false,
			isLoggedIn: false,
			isNoUser: true,
			isOwner: false,
			orphanedVotes: 0,
			shareToken: '',
			userId: '',
			userRole: '',
			countVotes: 0,
			yesVotes: 0,
			noVotes: 0,
			maybeVotes: 0,
		},
		permissions: {
			addOptions: false,
			addShares: false,
			addSharesExternal: false,
			archive: false,
			changeForeignVotes: false,
			changeOwner: false,
			clone: false,
			comment: false,
			confirmOptions: false,
			deanonymize: false,
			delete: false,
			edit: false,
			reorderOptions: false,
			seeResults: false,
			seeUsernames: false,
			subscribe: false,
			takeOver: false,
			view: false,
			vote: false,
		},
		revealParticipants: false,
		sortParticipants: 'alphabetical',
		meta: {
			chunking: {
				size: 0,
				loaded: 0,
			},
			status: 'loaded',
		},
	}),

	getters: {
		viewMode(state): ViewMode {
			const sessionStore = useSessionStore()
			if (state.type === 'textPoll') {
				return sessionStore.viewModeTextPoll
			}

			if (state.type === 'datePoll') {
				return sessionStore.viewModeDatePoll
			}
			return 'table-view'
		},

		getProposalsOptions(): {
			value: AllowProposals
			label: string
		}[] {
			return [
				{
					value: 'disallow',
					label: t('polls', 'Disallow proposals'),
				},
				{
					value: 'allow',
					label: t('polls', 'Allow proposals'),
				},
			]
		},

		displayResults(state): boolean {
			return (
				state.configuration.showResults === 'always'
				|| (state.configuration.showResults === 'closed'
					&& !this.status.isExpired)
			)
		},

		isProposalOpen(): boolean {
			return this.isProposalAllowed && !this.isProposalExpired
		},

		isProposalAllowed(state): boolean {
			return (
				state.configuration.allowProposals === 'allow'
				|| state.configuration.allowProposals === 'review'
			)
		},

		isConfirmationAllowed(state): boolean {
			return state.permissions.confirmOptions || !this.isClosed
		},

		isOptionCloneAllowed(state): boolean {
			return !this.isClosed && state.permissions.edit
		},

		isProposalExpired(state): boolean {
			return (
				this.isProposalAllowed
				&& state.configuration.proposalsExpire > 0
				&& moment.unix(state.configuration.proposalsExpire).diff() < 0
			)
		},

		isProposalExpirySet(state): boolean {
			return this.isProposalAllowed && state.configuration.proposalsExpire > 0
		},

		proposalsExpireRelative(state): string {
			return moment.unix(state.configuration.proposalsExpire).fromNow()
		},

		proposalsExpire_d(state): Date {
			return moment.unix(state.configuration.proposalsExpire)._d
		},

		isClosed(state): boolean {
			return (
				state.status.isExpired
				|| (state.configuration.expire > 0
					&& moment.unix(state.configuration.expire).diff() < 1000)
			)
		},

		descriptionMarkDown(): string {
			marked.use(gfmHeadingId(markedPrefix))
			return DOMPurify.sanitize(
				marked.parse(this.configuration.description).toString(),
			)
		},
	},

	actions: {
		reset(): void {
			this.$reset()
		},

		setViewMode(viewMode: ViewMode): void {
			const sessionStore = useSessionStore()
			sessionStore.setViewMode(this.type, viewMode)
		},

		setProposalExpiration(payload: { expire: number }): void {
			this.configuration.proposalsExpire = moment(payload.expire).unix()
			this.write()
		},

		setExpiration(payload: { expire: number }): void {
			this.configuration.proposalsExpire = moment(payload.expire).unix()
			this.write()
		},

		async resetPoll(): Promise<void> {
			const votesStore = useVotesStore()
			const optionsStore = useOptionsStore()
			// const sharesStore = useSharesStore()
			// const commentsStore = useCommentsStore()
			const subscriptionStore = useSubscriptionStore()
			this.$reset()
			votesStore.$reset()
			optionsStore.$reset()
			// sharesStore.$reset()
			// commentsStore.$reset()
			subscriptionStore.reset()
		},

		async load(pollId: number | null = null): Promise<void> {
			const votesStore = useVotesStore()
			const sessionStore = useSessionStore()
			const optionsStore = useOptionsStore()
			// const sharesStore = useSharesStore()
			// const commentsStore = useCommentsStore()
			const subscriptionStore = useSubscriptionStore()

			this.meta.status = 'loading'

			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.getPoll(sessionStore.route.params.token)
					}
					if (sessionStore.route.name === 'vote') {
						return PollsAPI.getPoll(pollId ?? sessionStore.currentPollId)
					}
				})()

				if (!response) {
					this.$reset()
					return
				}

				await Promise.all([
					this.$patch(response.data.poll),
					votesStore.load(),
					optionsStore.load(),
					// sharesStore.load(),
					// commentsStore.load(),
					subscriptionStore.load(),
				])

				this.meta.status = 'loaded'
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.meta.status = 'error'
				Logger.error('Error loading poll', { error })
				throw error
			}
		},

		async add(payload: { type: PollType; title: string }): Promise<Poll | void> {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.addPoll(payload.type, payload.title)
				return response.data.poll
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error adding poll:', {
					error,
					state: this.$state,
				})
				throw error
			} finally {
				pollsStore.load()
			}
		},

		async lockAnonymous(): Promise<void> {
			try {
				await PollsAPI.lockAnonymous(this.id)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error locking poll to anonymous:', {
					error,
					state: this.$state,
				})
				throw error
			} finally {
				// reload the poll
				this.load()
			}
		},

		/* Load all necessary stores based on the configuration change.
		 * Avoid loading all dependent stores, if not necessary and reduce
		 * the amount of db access and expensive mutations.
		 *
		 * TODO: Currently this is based on the changes reported by the API.
		 * For large polls this is still bad UX, since loading of the poll
		 * can take a while. Until then depending updates of the UI are
		 * also delayed.
		 *
		 * We keep it for the moment to avoid breaking changes.
		 */
		async loadConsistentPoll(changes: Partial<Poll>): Promise<void> {
			const dispatches = new Set()

			if (
				changes.configuration?.maxVotesPerUser !== undefined
				|| changes.configuration?.maxVotesPerOption !== undefined
				|| changes.configuration?.hideBookedUp !== undefined
			) {
				const optionsStore = useOptionsStore()
				dispatches.add(optionsStore.load())
			}

			if (
				changes.configuration?.allowComment !== undefined
				|| changes.configuration?.forceConfidentialComments !== undefined
			) {
				const commentsStore = useCommentsStore()
				dispatches.add(commentsStore.load())
			}

			if (changes.configuration?.anonymous !== undefined) {
				const votesStore = useVotesStore()
				dispatches.add(votesStore.load())
			}

			const pollsStore = usePollsStore()
			dispatches.add(pollsStore.load())
			Promise.all(dispatches)
		},

		write(): void {
			const debouncedLoad = this.$debounce(async () => {
				if (this.configuration.title === '') {
					showError(t('polls', 'Title must not be empty!'))
					return
				}

				try {
					const response = await PollsAPI.writePoll(
						this.id,
						this.configuration,
					)
					this.$patch(response.data.changes)
					emit(Event.UpdatePoll, {
						store: 'poll',
						message: t('polls', 'Poll updated'),
					})
					this.loadConsistentPoll(response.data.changes)
				} catch (error) {
					if ((error as AxiosError)?.code === 'ERR_CANCELED') {
						return
					}
					Logger.error('Error updating poll:', {
						error,
						poll: this.$state,
					})
					this.load()
					showError(t('polls', 'Error writing poll'))
					throw error
				}
			}, 500)
			debouncedLoad()
		},

		async close(): Promise<void> {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.closePoll(this.id)
				this.$patch(response.data.poll)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error closing poll', {
					error,
					pollId: this.id,
				})
				this.load()
				throw error
			} finally {
				pollsStore.load()
			}
		},

		async reopen(): Promise<void> {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.reopenPoll(this.id)
				this.$patch(response.data.poll)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error reopening poll', {
					error,
					pollId: this.id,
				})
				this.load()
				throw error
			} finally {
				pollsStore.load()
			}
		},

		async toggleArchive(payload: { pollId: number }): Promise<void> {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.toggleArchive(payload.pollId)
				if (this.id === payload.pollId) {
					this.$patch(response.data.poll)
				}
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error archiving/restoring', {
					error,
					payload,
				})
				throw error
			} finally {
				pollsStore.load()
			}
		},
	},
})
