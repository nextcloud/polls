/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import moment from '@nextcloud/moment'
import acl from './subModules/acl.js'
import { uniqueArrayOfObjects } from '../../helpers/index.js'
import { PollsAPI, PublicAPI } from '../../Api/index.js'

const defaultPoll = () => ({
	id: 0,
	type: 'datePoll',
	title: '',
	description: '',
	descriptionSafe: '',
	created: 0,
	expire: 0,
	deleted: 0,
	access: 'private',
	anonymous: 0,
	allowComment: 0,
	allowMaybe: 0,
	allowProposals: 'disallow',
	proposalsExpire: 0,
	showResults: 'always',
	adminAccess: 0,
	hideBookedUp: 0,
	useNo: 1,
	autoReminder: false,
	revealParticipants: false,
	limits: {
		maxVotesPerOption: 0,
		maxVotesPerUser: 0,
	},
	status: {
		lastInteraction: 0,
	},
	currentUserStatus: {
		userRole: 'none',
		isLocked: false,
		orphanedVotes: 0,
		yesVotes: 0,
		countVotes: 0,
	},
	owner: {
		userId: '',
		displayName: '',
		isNoUser: false,
	},
})

const namespaced = true
const modules = { acl }
const state = defaultPoll()

const mutations = {
	set(state, payload) {
		Object.assign(state, payload.poll)
	},

	reset(state) {
		Object.assign(state, defaultPoll())
	},

	setProperty(state, payload) {
		Object.assign(state, payload)
	},

	setLimit(state, payload) {
		Object.assign(state.limits, payload)
	},

	setDescriptionSafe(state, payload) {
		state.descriptionSafe = payload.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
	},
	switchSafeTable(state, toStatus) {
		state.revealParticipants = toStatus ?? !state.revealParticipants
	},
}

const getters = {
	viewMode: (state, getters, rootState, rootGetters) => {
		if (state.type === 'textPoll') {
			return rootGetters['settings/viewTextPoll']
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
		return t('polls', 'Date poll')
	},

	answerSequence: (state, getters, rootState) => {
		const noString = rootState.poll.useNo ? 'no' : ''
		if (state.allowMaybe) {
			return [noString, 'yes', 'maybe']
		}
		return [noString, 'yes']

	},

	participants: (state, getters, rootState) => {
		const participants = getters.participantsVoted

		// add current user, if not among participants and voting is allowed
		if (!participants.find((participant) => participant.userId === state.acl.currentUser.userId) && state.acl.currentUser.userId && state.acl.permissions.vote) {
			participants.push({
				userId: state.acl.currentUser.userId,
				displayName: state.acl.currentUser.displayName,
				isNoUser: state.acl.currentUser.isNoUser,
			})
		}

		return participants
	},

	safeParticipants: (state, getters) => {
		if (getters.safeTable) {
			return [{
				userId: state.acl.currentUser.userId,
				displayName: state.acl.currentUser.displayName,
				isNoUser: state.acl.currentUser.isNoUser,
			}]
		}
		return getters.participants
	},

	participantsVoted: (state, getters, rootState) => uniqueArrayOfObjects(rootState.votes.list.map((vote) => (
		vote.user
	))),

	proposalsOptions: () => [
		{ value: 'disallow', label: t('polls', 'Disallow proposals') },
		{ value: 'allow', label: t('polls', 'Allow proposals') },
	],

	displayResults: (state, getters) => state.showResults === 'always' || (state.showResults === 'closed' && !getters.closed),
	proposalsAllowed: (state) => state.allowProposals === 'allow' || state.allowProposals === 'review',
	proposalsOpen: (state, getters) => getters.proposalsAllowed && !getters.proposalsExpired,
	proposalsExpired: (state, getters) => getters.proposalsAllowed && state.proposalsExpire && moment.unix(state.proposalsExpire).diff() < 0,
	proposalsExpirySet: (state, getters) => getters.proposalsAllowed && state.proposalsExpire,
	proposalsExpireRelative: (state) => moment.unix(state.proposalsExpire).fromNow(),
	isClosed: (state) => (state.expire > 0 && moment.unix(state.expire).diff() < 1000),
	safeTable: (state, getters, rootState) => !state.revealParticipants && getters.countCells > rootState.settings.user.performanceThreshold,
	countParticipants: (state, getters) => getters.participants.length,
	countHiddenParticipants: (state, getters) => getters.participants.length - getters.safeParticipants.length,
	countSafeParticipants: (state, getters) => getters.safeParticipants.length,
	countParticipantsVoted: (state, getters) => getters.participantsVoted.length,
	countCells: (state, getters, rootState, rootGetters) => getters.countParticipants * rootGetters['options/count'],
}

const actions = {

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
				context.commit('acl/reset')
				return
			}
			context.commit('switchSafeTable', false)
			context.commit('set', response.data)
			context.commit('acl/set', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.debug('Error loading poll', { error: e })
			throw e
		}
	},

	async add(context, payload) {
		try {
			const response = await PollsAPI.addPoll(payload.type, payload.title)
			return response
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error adding poll:', { error: e.response }, { state: context.state })
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async update(context) {
		try {
			const response = await PollsAPI.updatePoll(context.state)
			context.commit('set', response.data)
			context.commit('acl/set', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error updating poll:', { error: e.response }, { poll: context.state })
			context.dispatch('get')
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
			context.dispatch('options/list', null, { root: true })
		}
	},

	async close(context) {
		try {
			const response = await PollsAPI.closePoll(context.state.id)
			context.commit('set', { poll: response.data.poll })
			context.commit('acl/set', { acl: response.data.acl })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error closing poll', { error: e.response }, { pollId: context.state.id })
			context.dispatch('get')
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async reopen(context) {
		try {
			const response = await PollsAPI.reopenPoll(context.state.id)
			context.commit('set', { poll: response.data.poll })
			context.commit('acl/set', { acl: response.data.acl })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error reopening poll', { error: e.response }, { pollId: context.state.id })
			context.dispatch('get')
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async toggleArchive(context, payload) {
		try {
			await PollsAPI.toggleArchive(payload.pollId)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error archiving/restoring', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async delete(context, payload) {
		try {
			await PollsAPI.deletePoll(payload.pollId)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error deleting poll', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},

	async clone(context, payload) {
		try {
			const response = await PollsAPI.clonePoll(payload.pollId)
			return response
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error cloning poll', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('polls/list', null, { root: true })
		}
	},
}

export default { namespaced, state, mutations, getters, actions, modules }
