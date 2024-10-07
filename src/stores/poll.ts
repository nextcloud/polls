/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { debounce } from 'lodash'
import DOMPurify from 'dompurify'
import { marked } from 'marked'
import { gfmHeadingId } from 'marked-gfm-heading-id'

import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import { Logger, uniqueArrayOfObjects } from '../helpers/index.ts'
import { PublicAPI, PollsAPI } from '../Api/index.js'
import { User, UserType } from '../Types/index.ts'

import { usePreferencesStore, ViewMode } from './preferences.ts'
import { useVotesStore, Answer } from './votes.ts'
import { useOptionsStore } from './options.ts'
import { usePollsStore } from './polls.ts'
import { useSessionStore } from './session.ts'
import { useSubscriptionStore } from './subscription.ts'
import { useSharesStore } from './shares.ts'
import { useCommentsStore } from './comments.ts'


export enum PollType {
	Text = 'textPoll',
	Date = 'datePoll',
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

export type PollConfiguration = {
	title: string
	description: string
	access: AccessType
	allowComment: boolean
	allowMaybe: boolean
	allowProposals: AllowProposals
	anonymous: boolean
	autoReminder: boolean
	expire: number
	hideBookedUp: boolean
	proposalsExpire: number
	showResults: ShowResults
	useNo: boolean
	maxVotesPerOption: number
	maxVotesPerUser: number
}

export type PollStatus = {
	lastInteraction: number
	created: number
	deleted: boolean
	expired: boolean
	relevantThreshold: number
	countOptions: number
	countParticipants: number
}

export type PollPermissions = {
	addOptions: boolean
	archive: boolean
	comment: boolean
	delete: boolean
	edit: boolean
	seeResults: boolean
	seeUsernames: boolean
	subscribe: boolean
	view: boolean
	vote: boolean
}

export type CurrentUserStatus = {
	userRole: UserType
	isLocked: boolean
	isInvolved: boolean
	isLoggedIn: boolean
	isNoUser: boolean
	isOwner: boolean
	userId: string
	orphanedVotes: number
	yesVotes: number
	countVotes: number
	shareToken: string
	groupInvitations: string[]
}

export type Poll = {
	id: number
	type: PollType
	descriptionSafe: string
	configuration: PollConfiguration
	owner: User
	status: PollStatus
	currentUserStatus: CurrentUserStatus
	permissions: PollPermissions
	revealParticipants: boolean
}

const markedPrefix = {
	prefix: 'desc-',
}

export const usePollStore = defineStore('poll', {
	state: (): Poll => ({
		id: 0,
		type: PollType.Date,
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
		owner: {
			displayName: '',
			emailAddress: '',
			subName: '',
			subtitle: '',
			isNoUser: false,
			desc: '',
			type: UserType.User,
			id: '',
			organisation: '',
			languageCode: '',
			localeCode: '',
			timeZone: '',
			categories: [],
		},
		status: {
			lastInteraction: 0,
			created: 0,
			deleted: false,
			expired: false,
			relevantThreshold: 0,
			countOptions: 0,
			countParticipants: 0,
		},
		currentUserStatus: {
			userRole: UserType.None,
			isLocked: false,
			isInvolved: false,
			isLoggedIn: false,
			isNoUser: true,
			isOwner: false,
			userId: '',
			orphanedVotes: 0,
			yesVotes: 0,
			countVotes: 0,
			shareToken: '',
			groupInvitations: [],
		},
		permissions: {
			addOptions: false,
			archive: false,
			comment: false,
			delete: false,
			edit: false,
			seeResults: false,
			seeUsernames: false,
			subscribe: false,
			view: false,
			vote: false,
		},
		revealParticipants: false,
	}),

	getters: {
		viewMode(state) {
			const preferencesStore = usePreferencesStore()
			if (state.type === PollType.Text) {
				return preferencesStore.viewTextPoll
			}
	
			if (state.type === PollType.Date) {
				return preferencesStore.viewDatePoll
			}
			return ViewMode.TableView
		},
	
		getNextViewMode() {
			const preferencesStore = usePreferencesStore()
			if (preferencesStore.viewModes.indexOf(this.viewMode) < 0) {
				return preferencesStore.viewModes[1]
			}
			return preferencesStore.viewModes[(preferencesStore.viewModes.indexOf(this.viewMode) + 1) % preferencesStore.viewModes.length]
	
		},
	
		typeName(state) {
			if (state.type === PollType.Text) {
				return t('polls', 'Text poll')
			}
			return t('polls', 'Date poll')
		},
	
		answerSequence(state) {
			const noString = state.configuration.useNo ? Answer.No : Answer.None
			if (state.configuration.allowMaybe) {
				return [noString, Answer.Yes, Answer.Maybe]
			}
			return [noString, Answer.Yes]
	
		},
	
		participants(state): User[] {
			const sessionStore = useSessionStore()
			const participants = this.participantsVoted
	
			// add current user, if not among participants and voting is allowed
			if (!participants.find((participant: User) => participant.id === sessionStore.currentUser.id) && sessionStore.currentUser.id && state.permissions.vote) {
				participants.push({
					id: sessionStore.currentUser.id,
					displayName: sessionStore.currentUser.displayName,
					isNoUser: sessionStore.currentUser.isNoUser,
				})
			}
	
			return participants
		},
	
		safeParticipants() {
			const sessionStore = useSessionStore()
			if (this.getSafeTable) {
				return [{
					id: sessionStore.currentUser.id,
					displayName: sessionStore.currentUser.displayName,
					isNoUser: sessionStore.currentUser.isNoUser,
				}]
			}
			return this.participants
		},
	
		participantsVoted(): User[] {
			const votesStore = useVotesStore()

			return uniqueArrayOfObjects(votesStore.list.map((vote) => (
				vote.user
			)))
		},
	
		getProposalsOptions: () => [
			{ value: AllowProposals.Disallow, label: t('polls', 'Disallow proposals') },
			{ value: AllowProposals.Allow, label: t('polls', 'Allow proposals') },
		],
	
		displayResults(state) {
			return state.configuration.showResults === ShowResults.Always || (state.configuration.showResults === ShowResults.Closed && !this.closed)
		},

		isProposalOpen() {
			return this.isProposalAllowed && !this.isProposalExpired
		},

		isProposalAllowed(state) {
			return state.configuration.allowProposals === AllowProposals.Allow || state.configuration.allowProposals === AllowProposals.Review
		},

		isProposalExpired(state) {
			return this.isProposalAllowed && state.configuration.proposalsExpire && moment.unix(state.configuration.proposalsExpire).diff() < 0
		},

		isProposalExpirySet(state) {
			return this.isProposalAllowed && state.configuration.proposalsExpire
		},

		proposalsExpireRelative(state) {
			return moment.unix(state.configuration.proposalsExpire).fromNow()
		},

		proposalsExpire_d(state) {
			return moment.unix(state.configuration.proposalsExpire)._d
		},

		isClosed(state) {
			return (state.configuration.expire > 0 && moment.unix(state.configuration.expire).diff() < 1000)
		},

		getSafeTable(state) {
			const preferencesStore = usePreferencesStore()
			return !state.revealParticipants && this.countCells > preferencesStore.user.performanceThreshold
		},

		countParticipants() {
			return this.participants.length
		},

		countHiddenParticipants() {
			return this.participants.length - this.safeParticipants.length
		},

		countSafeParticipants() {
			return this.safeParticipants.length
		},

		countParticipantsVoted() {
			return this.participantsVoted.length
		},

		countCells() {
			const optionsStore = useOptionsStore()
			return this.countParticipants * optionsStore.count
		},

		descriptionMarkUp() {
			marked.use(gfmHeadingId(markedPrefix))
			return DOMPurify.sanitize(marked.parse(this.configuration.description).toString())
		},
	},
	
	actions: {
		reset() {
			this.$reset()
		},

		setProposalExpiration(payload: { expire: number }) {
			this.configuration.proposalExpire = moment(payload.expire).unix()
			this.update()
		},

		setExpiration(payload: { expire: number }) {
			this.configuration.proposalExpire = moment(payload.expire).unix()
			this.update()
		},

		async resetPoll() {
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

		async load() {
			const votesStore = useVotesStore()
			const sessionStore = useSessionStore()
			const optionsStore = useOptionsStore()
			const sharesStore = useSharesStore()
			const commentsStore = useCommentsStore()
			const subscriptionStore = useSubscriptionStore()
			try {
				let response = null

				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.getPoll(sessionStore.route.params.token)
				} else if (sessionStore.route.name === 'vote') {
					response = await PollsAPI.getFullPoll(sessionStore.route.params.id)
				} else {
					this.reset()
					return
				}
				
				this.$patch(response.data.poll)
				votesStore.list = response.data.votes
				optionsStore.list = response.data.options
				sharesStore.list = response.data.shares
				commentsStore.list = response.data.comments
				subscriptionStore.subscribed = response.data.subscribed
				sessionStore.$patch(response.data.acl)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading poll', { error: error.response })
				throw error
			}
		},
	
		async add(payload: { type: PollType; title: string }) {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.addPoll(payload.type, payload.title)
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error adding poll:', { error, state: this.$state })
				throw error
			} finally {
				pollsStore.load()
			}
		},
	
		write: debounce(async function() {
			const pollsStore = usePollsStore()
			if (this.configuration.title === '') {
				showError(t('polls', 'Title must not be empty!'))
				return
			}

			try {
				const response = await PollsAPI.writePoll(this.id, this.configuration)
				this.$patch(response.data.poll)
				emit('polls:poll:updated', { store: 'poll', message: t('polls', 'Poll updated') })
				
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error updating poll:', { error, poll: this.$state })
				showError(t('polls', 'Error writing poll'))
				this.load()
				throw error
			} finally {
				pollsStore.load()
			}
		}, 500),

		async close() {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.closePoll(this.id)
				this.$patch(response.data.poll)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error closing poll', { error, pollId: this.id })
				this.load()
				throw error
			} finally {
				pollsStore.load()
			}
		},
	
		async reopen() {
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.reopenPoll(this.id)
				this.$patch(response.data.poll)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error reopening poll', { error, pollId: this.id })
				this.load()
				throw error
			} finally {
				pollsStore.load()
			}
		},
	
		async toggleArchive(payload: { pollId: number }) {
			const pollsStore = usePollsStore()

			try {
				await PollsAPI.toggleArchive(payload.pollId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error archiving/restoring', { error, payload })
				throw error
			} finally {
				pollsStore.load()
			}
		},
	
		async delete(payload: { pollId: number }) {
			const pollsStore = usePollsStore()

			try {
				await PollsAPI.deletePoll(payload.pollId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting poll', { error, payload })
				throw error
			} finally {
				pollsStore.load()
			}
		},
	
		async clone(payload: { pollId: number }) {
			const pollsStore = usePollsStore()
			try {
				const response = await PollsAPI.clonePoll(payload.pollId)
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error cloning poll', { error, payload })
				throw error
			} finally {
				pollsStore.load()
			}
		},
	},
})
