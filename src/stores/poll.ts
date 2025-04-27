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

import { Logger } from '../helpers/index.ts'
import { PublicAPI, PollsAPI } from '../Api/index.ts'
import { createDefault, Event, User, UserType } from '../Types/index.ts'

import { usePreferencesStore, ViewMode } from './preferences.ts'
import { useVotesStore, Answer } from './votes.ts'
import { useOptionsStore } from './options.ts'
import { usePollsStore } from './polls.ts'
import { useSessionStore } from './session.ts'
import { useSubscriptionStore } from './subscription.ts'
import { useSharesStore } from './shares.ts'
import { useCommentsStore } from './comments.ts'
import { AxiosError } from '@nextcloud/axios'

export enum PollType {
	Text = 'textPoll',
	Date = 'datePoll',
}

type PollTypesType = {
	name: string
}

export const pollTypes: Record<PollType, PollTypesType> = {
	[PollType.Text]: {
		name: t('polls', 'Text poll'),
	},
	[PollType.Date]: {
		name: t('polls', 'Date poll'),
	},
}

export enum VoteVariant {
	Simple = 'simple',
}

export enum AccessType {
	Private = 'private',
	Open = 'open',
}

export enum ShowResults {
	Always = 'always',
	Closed = 'closed',
	Never = 'never',
}

export enum AllowProposals {
	Allow = 'allow',
	Disallow = 'disallow',
	Review = 'review',
}

export enum SortParticipants {
	Alphabetical = 'alphabetical',
	VoteCount = 'voteCount',
	Unordered = 'unordered',
}

export type PollConfiguration = {
	access: AccessType
	allowComment: boolean
	allowMaybe: boolean
	allowProposals: AllowProposals
	anonymous: boolean
	autoReminder: boolean
	description: string
	expire: number
	hideBookedUp: boolean
	maxVotesPerOption: number
	maxVotesPerUser: number
	proposalsExpire: number
	showResults: ShowResults
	title: string
	useNo: boolean
}

export type PollStatus = {
	anonymizeLevel: string
	lastInteraction: number
	created: number
	isAnonymous: boolean
	isArchived: boolean
	isExpired: boolean
	isRealAnonymous: boolean
	relevantThreshold: number
	deletionDate: number
	archivedDate: number
	countOptions: number
	countParticipants: number
	countProposals: number
}

export type PollPermissions = {
	addOptions: boolean
	addShares: boolean
	addSharesExternal: boolean
	archive: boolean
	changeForeignVotes: boolean
	clone: boolean
	comment: boolean
	confirmOptions: boolean
	deanonymize: boolean
	delete: boolean
	edit: boolean
	reorderOptions: boolean
	seeResults: boolean
	seeUsernames: boolean
	shiftOptions: boolean
	subscribe: boolean
	view: boolean
	vote: boolean
}

export type CurrentUserStatus = {
	countVotes: number
	groupInvitations: string[]
	isInvolved: boolean
	isLocked: boolean
	isLoggedIn: boolean
	isNoUser: boolean
	isOwner: boolean
	orphanedVotes: number
	shareToken: string
	userId: string
	userRole: UserType
	yesVotes: number
}

export type Poll = {
	id: number
	type: PollType
	voteVariant: VoteVariant
	descriptionSafe: string
	configuration: PollConfiguration
	owner: User
	status: PollStatus
	currentUserStatus: CurrentUserStatus
	permissions: PollPermissions
	revealParticipants: boolean
	sortParticipants: SortParticipants
}

const markedPrefix = {
	prefix: 'desc-',
}

export const usePollStore = defineStore('poll', {
	state: (): Poll => ({
		id: 0,
		type: PollType.Date,
		voteVariant: VoteVariant.Simple,
		descriptionSafe: '',
		configuration: {
			title: '',
			description: '',
			access: AccessType.Private,
			allowComment: false,
			allowMaybe: false,
			allowProposals: AllowProposals.Disallow,
			anonymous: false,
			autoReminder: false,
			expire: 0,
			hideBookedUp: false,
			proposalsExpire: 0,
			showResults: ShowResults.Always,
			useNo: true,
			maxVotesPerOption: 0,
			maxVotesPerUser: 0,
		},
		owner: createDefault<User>(),
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
			countOptions: 0,
			countParticipants: 0,
			countProposals: 0,
		},
		currentUserStatus: {
			countVotes: 0,
			groupInvitations: [],
			isInvolved: false,
			isLocked: false,
			isLoggedIn: false,
			isNoUser: true,
			isOwner: false,
			orphanedVotes: 0,
			shareToken: '',
			userId: '',
			userRole: UserType.None,
			yesVotes: 0,
		},
		permissions: {
			addOptions: false,
			addShares: false,
			addSharesExternal: false,
			archive: false,
			changeForeignVotes: false,
			clone: false,
			comment: false,
			confirmOptions: false,
			deanonymize: false,
			delete: false,
			edit: false,
			reorderOptions: false,
			shiftOptions: false,
			seeResults: false,
			seeUsernames: false,
			subscribe: false,
			view: false,
			vote: false,
		},
		revealParticipants: false,
		sortParticipants: SortParticipants.Alphabetical,
	}),

	getters: {
		viewMode(state): ViewMode {
			const preferencesStore = usePreferencesStore()
			if (state.type === PollType.Text) {
				return preferencesStore.viewTextPoll
			}

			if (state.type === PollType.Date) {
				return preferencesStore.viewDatePoll
			}
			return ViewMode.TableView
		},

		answerSequence(state): Answer[] {
			const noString = state.configuration.useNo ? Answer.No : Answer.None
			if (state.configuration.allowMaybe) {
				return [noString, Answer.Yes, Answer.Maybe]
			}
			return [noString, Answer.Yes]
		},

		safeParticipants(): User[] {
			const sessionStore = useSessionStore()
			const votesStore = useVotesStore()
			if (this.getSafeTable) {
				return [sessionStore.currentUser]
			}
			return votesStore.sortedParticipants
		},

		getProposalsOptions(): {
			value: AllowProposals
			label: string
		}[] {
			return [
				{
					value: AllowProposals.Disallow,
					label: t('polls', 'Disallow proposals'),
				},
				{
					value: AllowProposals.Allow,
					label: t('polls', 'Allow proposals'),
				},
			]
		},

		displayResults(state): boolean {
			return (
				state.configuration.showResults === ShowResults.Always
				|| (state.configuration.showResults === ShowResults.Closed
					&& !this.status.isExpired)
			)
		},

		isProposalOpen(): boolean {
			return this.isProposalAllowed && !this.isProposalExpired
		},

		isProposalAllowed(state): boolean {
			return (
				state.configuration.allowProposals === AllowProposals.Allow
				|| state.configuration.allowProposals === AllowProposals.Review
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

		getSafeTable(state): boolean {
			const preferencesStore = usePreferencesStore()
			return (
				!state.revealParticipants
				&& this.countCells > preferencesStore.user.performanceThreshold
			)
		},

		// count the number of participants (including current user, if has not voted yet)
		countParticipants(): number {
			const votesStore = useVotesStore()
			return votesStore.sortedParticipants.length
		},

		countHiddenParticipants(): number {
			const votesStore = useVotesStore()
			return votesStore.sortedParticipants.length - this.safeParticipants.length
		},

		// count the number of safe participants (including current user, if has not voted yet)
		countSafeParticipants(): number {
			return this.safeParticipants.length
		},

		countCells(): number {
			const optionsStore = useOptionsStore()
			return this.countParticipants * optionsStore.count
		},

		descriptionMarkUp(): string {
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
			const sharesStore = useSharesStore()
			const commentsStore = useCommentsStore()
			const subscriptionStore = useSubscriptionStore()
			this.$reset()
			votesStore.$reset()
			optionsStore.$reset()
			sharesStore.$reset()
			commentsStore.$reset()
			subscriptionStore.$reset()
		},

		async load(): Promise<void> {
			const votesStore = useVotesStore()
			const sessionStore = useSessionStore()
			const optionsStore = useOptionsStore()
			const sharesStore = useSharesStore()
			const commentsStore = useCommentsStore()
			const subscriptionStore = useSubscriptionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.getPoll(sessionStore.route.params.token)
					}
					if (sessionStore.route.name === 'vote') {
						return PollsAPI.getFullPoll(sessionStore.currentPollId)
					}
					return null
				})()

				if (!response) {
					this.$reset()
					return
				}

				this.$patch(response.data.poll)
				votesStore.list = response.data.votes
				optionsStore.list = response.data.options
				sharesStore.list = response.data.shares
				commentsStore.list = response.data.comments
				subscriptionStore.subscribed = response.data.subscribed
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
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

		async LockAnonymous(): Promise<void> {
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

		write(): void {
			const pollsStore = usePollsStore()

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
					this.$patch(response.data.poll)
					emit(Event.UpdatePoll, {
						store: 'poll',
						message: t('polls', 'Poll updated'),
					})
				} catch (error) {
					if ((error as AxiosError)?.code === 'ERR_CANCELED') {
						return
					}
					Logger.error('Error updating poll:', {
						error,
						poll: this.$state,
					})
					showError(t('polls', 'Error writing poll'))
					throw error
				} finally {
					this.load()
					pollsStore.load()
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
				await PollsAPI.toggleArchive(payload.pollId)
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

		async delete(payload: { pollId: number }): Promise<void> {
			const pollsStore = usePollsStore()

			try {
				await PollsAPI.deletePoll(payload.pollId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting poll', {
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
