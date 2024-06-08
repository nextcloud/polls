/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, PollsAPI } from '../Api/index.js'
import { User } from '../Interfaces/interfaces.ts'
import { Logger, uniqueArrayOfObjects } from '../helpers/index.js'
import moment from '@nextcloud/moment'
import { usePreferencesStore } from './preferences.ts'
import { useAclStore } from './acl.ts'
import { useVotesStore } from './votes.ts'
import { useOptionsStore } from './options.ts'
import { usePollsStore } from './polls.ts'
import { useRouterStore } from './router.ts'
import { t } from '@nextcloud/l10n'


export type PollType = 'datePoll' | 'textPoll'
export type AccessType = 'private' | 'open'
export type ShowResultsType = 'always' | 'closed' | 'never'
export type allowProposalsType = 'allow' | 'disallow' | 'review'

export interface PollConfiguration {
	title: string
	description: string
	access: AccessType
	allowComment: boolean
	allowMaybe: boolean
	allowProposals: allowProposalsType
	anonymous: boolean
	autoReminder: boolean
	expire: number
	hideBookedUp: boolean
	proposalsExpire: number
	showResults: ShowResultsType
	useNo: boolean
	maxVotesPerOption: number
	maxVotesPerUser: number
}

export interface PollStatus {
	lastInteraction: number
	created: number
	deleted: boolean
	expired: boolean
	relevantThreshold: number
	countOptions: number
}

export interface PollPermissions {
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

export interface CurrentUserStatus {
	userRole: string
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

export interface Poll {
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

export const usePollStore = defineStore('poll', {
	state: (): Poll => ({
		id: 0,
		type: 'datePoll',
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
			expire: 0,
			hideBookedUp: false,
			proposalsExpire: 0,
			showResults: 'always',
			useNo: true,
			maxVotesPerOption: 0,
			maxVotesPerUser: 0,
		},
		owner: {
			userId: '',
			displayName: '',
			emailAddress: '',
			subName: '',
			subtitle: '',
			isNoUser: false,
			desc: '',
			type: 'user',
			id: '',
			user: '',
			organisation: '',
			languageCode: '',
			localeCode: '',
			timeZone: '',
			icon: '',
			categories: [],
		},
		status: {
			lastInteraction: 0,
			created: 0,
			deleted: false,
			expired: false,
			relevantThreshold: 0,
			countOptions: 0,
		},
		currentUserStatus: {
			userRole: '',
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
			if (state.type === 'textPoll') {
				return preferencesStore.$state.user.defaultViewTextPoll
			}
	
			if (state.type === 'datePoll') {
				return preferencesStore.$state.user.defaultViewDatePoll
			}
			return 'table-view'
		},
	
		getNextViewMode() {
			const preferencesStore = usePreferencesStore()
			if (preferencesStore.viewModes.indexOf(this.viewMode) < 0) {
				return preferencesStore.viewModes[1]
			}
			return preferencesStore.viewModes[(preferencesStore.viewModes.indexOf(this.viewMode) + 1) % preferencesStore.viewModes.length]
	
		},
	
		typeName(state) {
			if (state.type === 'textPoll') {
				return t('polls', 'Text poll')
			}
			return t('polls', 'Date poll')
		},
	
		answerSequence(state) {
			const noString = state.configuration.useNo ? 'no' : ''
			if (state.configuration.allowMaybe) {
				return [noString, 'yes', 'maybe']
			}
			return [noString, 'yes']
	
		},
	
		participants(state) {
			const aclStore = useAclStore()
			const participants = this.participantsVoted
	
			// add current user, if not among participants and voting is allowed
			if (!participants.find((participant) => participant.userId === aclStore.currentUser.userId) && aclStore.currentUser.userId && state.permissions.vote) {
				participants.push({
					userId: aclStore.currentUser.userId,
					displayName: aclStore.currentUser.displayName,
					isNoUser: aclStore.currentUser.isNoUser,
				})
			}
	
			return participants
		},
	
		safeParticipants() {
			const aclStore = useAclStore()
			if (this.getSafeTable) {
				return [{
					userId: aclStore.currentUser.userId,
					displayName: aclStore.currentUser.displayName,
					isNoUser: aclStore.currentUser.isNoUser,
				}]
			}
			return this.participants
		},
	
		participantsVoted() {
			const votesStore = useVotesStore()

			return uniqueArrayOfObjects(votesStore.list.map((vote) => (
				vote.user
			)))
		},
	
		getProposalsOptions: () => [
			{ value: 'disallow', label: t('polls', 'Disallow proposals') },
			{ value: 'allow', label: t('polls', 'Allow proposals') },
		],
	
		displayResults(state) {
			return state.configuration.showResults === 'always' || (state.configuration.showResults === 'closed' && !this.closed)
		},

		isProposalOpen() {
			return this.isProposalAllowed && !this.isProposalExpired
		},

		isProposalAllowed(state) {
			return state.configuration.allowProposals === 'allow' || state.configuration.allowProposals === 'review'
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

	},
	
	actions: {
		reset() {
			this.$reset()
		},
	
		async load() {
			const routerStore = useRouterStore()
			try {
				let response = null

				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.getPoll(routerStore.params.token)
				} else if (routerStore.name === 'vote') {
					response = await PollsAPI.getPoll(routerStore.params.id)
				} else {
					this.reset()
					return
				}
				
				this.$patch(response.data.poll)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading poll', { error })
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
	
		async update() {
			const optionsStore = useOptionsStore()
			const pollsStore = usePollsStore()

			try {
				const response = await PollsAPI.updatePoll(this.id, this.configuration)
				this.$patch(response.data.poll)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error updating poll:', { error, poll: this.$state })
				this.load()
				throw error
			} finally {
				pollsStore.load()
				optionsStore.load()
			}
		},
	
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
