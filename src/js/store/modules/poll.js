/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import moment from '@nextcloud/moment'
import { uniqueArrayOfObjects, Logger } from '../../helpers/index.js'
import { PollsAPI, PublicAPI } from '../../Api/index.js'

const DEFAULT_CHOSEN_RANK = ['1', '2', '3'] ;

const defaultPoll = () => ({
	id: 0,
	type: 'datePoll',
	descriptionSafe: '',
	configuration: {
		title: '',
		description: '',
		access: 'private',
		allowComment: false,
		allowMaybe: false,
		chosenRank: JSON.stringify(DEFAULT_CHOSEN_RANK),
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
		isNoUser: false,
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
})

const namespaced = true
const state = defaultPoll()

const mutations = {
 	SET_CHOOSEN_RANK(state, chosenRank) {
    			state.configuration.chosenRank = JSON.stringify(chosenRank);
  	},

	set(state, payload) {
		Object.assign(state, payload.poll)
	},

	reset(state) {
		Object.assign(state, defaultPoll())
	},

	setProperty(state, payload) {
		Object.assign(state.configuration, payload)
	},

	setLimit(state, payload) {
		Object.assign(state.configuration, payload)
	},

	setDescriptionSafe(state, payload) {
		state.descriptionSafe = payload.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
	},
	switchSafeTable(state, toStatus) {
		state.revealParticipants = toStatus ?? !state.revealParticipants
	},
}

const getters = {
	getChosenRank: (state) => {
		try {
		     const parsed = JSON.parse(state.configuration.chosenRank);
		   return Array.isArray(parsed) ? parsed : DEFAULT_CHOSEN_RANK;
    		} catch {
      		return DEFAULT_CHOSEN_RANK;
    		}
	},

	viewMode: (state, getters, rootState, rootGetters) => {
		if (state.type === 'textPoll') {
			return rootGetters['settings/viewTextPoll']
		}
		if (state.type === 'textRankPollRank') {
			return rootGetters['settings/viewTextRankPoll']
		}
		if (state.type === 'datePoll') {
			return rootGetters['settings/viewDatePoll']
		}
		return 'table-view'
	},

	getNextViewMode: (state, getters, rootState) => {
		if (rootState.settings.viewModes.indexOf(getters.viewMode) < 0) {
			return rootState.settings.viewModes[1]
		}
		return rootState.settings.viewModes[(rootState.settings.viewModes.indexOf(getters.viewMode) + 1) % rootState.settings.viewModes.length]

	},

	typeName: (state) => {
		if (state.type === 'textPoll') {
			return t('polls', 'Text poll')
		}
		if (state.type === 'textRankPoll') {
			return t('polls', 'Text Rank poll')
		}
		return t('polls', 'Date poll')
	},

	answerSequence: (state) => {
		const noString = state.configuration.useNo ? 'no' : ''
		if (state.configuration.allowMaybe) {
			return [noString, 'yes', 'maybe']
		}
		return [noString, 'yes']

	},

	participants: (state, getters, rootState) => {
		const participants = getters.participantsVoted

		// add current user, if not among participants and voting is allowed
		if (!participants.find((participant) => participant.userId === rootState.acl.currentUser.userId) && rootState.acl.currentUser.userId && state.permissions.vote) {
			participants.push({
				userId: rootState.acl.currentUser.userId,
				displayName: rootState.acl.currentUser.displayName,
				isNoUser: rootState.acl.currentUser.isNoUser,
			})
		}

		return participants
	},

	safeParticipants: (state, getters, rootState) => {
		if (getters.getSafeTable) {
			return [{
				userId: rootState.acl.currentUser.userId,
				displayName: rootState.acl.currentUser.displayName,
				isNoUser: rootState.acl.currentUser.isNoUser,
			}]
		}
		return getters.participants
	},

	participantsVoted: (state, getters, rootState) => uniqueArrayOfObjects(rootState.votes.list.map((vote) => (
		vote.user
	))),

	getProposalsOptions: () => [
		{ value: 'disallow', label: t('polls', 'Disallow proposals') },
		{ value: 'allow', label: t('polls', 'Allow proposals') },
	],

	displayResults: (state, getters) => state.configuration.showResults === 'always' || (state.configuration.showResults === 'closed' && !getters.closed),
	isProposalAllowed: (state) => state.configuration.allowProposals === 'allow' || state.configuration.allowProposals === 'review',
	isProposalOpen: (state, getters) => getters.isProposalAllowed && !getters.isProposalExpired,
	isProposalExpired: (state, getters) => getters.isProposalAllowed && state.configuration.proposalsExpire && moment.unix(state.configuration.proposalsExpire).diff() < 0,
	isProposalExpirySet: (state, getters) => getters.isProposalAllowed && state.configuration.proposalsExpire,
	proposalsExpireRelative: (state) => moment.unix(state.configuration.proposalsExpire).fromNow(),
	isClosed: (state) => (state.configuration.expire > 0 && moment.unix(state.configuration.expire).diff() < 1000),
	getSafeTable: (state, getters, rootState) => !state.revealParticipants && getters.countCells > rootState.settings.user.performanceThreshold,
	countParticipants: (state, getters) => getters.participants.length,
	countHiddenParticipants: (state, getters) => getters.participants.length - getters.safeParticipants.length,
	countSafeParticipants: (state, getters) => getters.safeParticipants.length,
	countParticipantsVoted: (state, getters) => getters.participantsVoted.length,
	countCells: (state, getters, rootState, rootGetters) => getters.countParticipants * rootGetters['options/count'],
}

const actions = {
  	updateChosenRank({ commit }, chosenRank) {
    		commit('SET_CHOOSEN_RANK', chosenRank);
  	},

	reset(context) {
		context.commit('reset')
	},

	async get(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getPoll(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await PollsAPI.getPoll(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}
			context.commit('switchSafeTable', false)
			context.commit('set', { poll: response.data.poll })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.debug('Error loading poll', { error })
			throw error
		}
	},

	async add(context, payload) {
		try {
			const response = await PollsAPI.addPoll(payload.type, payload.title)
			return response
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error adding poll:', { error, state: context.state })
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async update(context) {
		try {
			const response = await PollsAPI.updatePoll(context.state.id, context.state.configuration)
			context.commit('set', { poll: response.data.poll })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error updating poll:', { error, poll: context.state })
			context.dispatch('get')
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
			context.dispatch('options/list', null, { root: true })
		}
	},

	async close(context) {
		try {
			const response = await PollsAPI.closePoll(context.state.id)
			context.commit('set', { poll: response.data.poll })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error closing poll', { error, pollId: context.state.id })
			context.dispatch('get')
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async reopen(context) {
		try {
			const response = await PollsAPI.reopenPoll(context.state.id)
			context.commit('set', { poll: response.data.poll })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error reopening poll', { error, pollId: context.state.id })
			context.dispatch('get')
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async toggleArchive(context, payload) {
		try {
			await PollsAPI.toggleArchive(payload.pollId)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error archiving/restoring', { error, payload })
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async delete(context, payload) {
		try {
			await PollsAPI.deletePoll(payload.pollId)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting poll', { error, payload })
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async clone(context, payload) {
		try {
			const response = await PollsAPI.clonePoll(payload.pollId)
			return response
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error cloning poll', { error, payload })
			throw error
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
