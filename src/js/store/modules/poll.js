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

import axios from '@nextcloud/axios'
import moment from '@nextcloud/moment'
import { generateUrl } from '@nextcloud/router'
import acl from './subModules/acl.js'
import { uniqueArrayOfObjects, uniqueParticipants } from '../../helpers/arrayHelper.js'

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
	voteLimit: 0,
	optionLimit: 0,
	showResults: 'always',
	adminAccess: 0,
	important: 0,
	hideBookedUp: 0,
	useNo: 1,
	autoReminder: false,
	owner: {
		userId: '',
		displayName: '',
		isNoUser: false,
	},
})

const state = defaultPoll()

const namespaced = true
const modules = {
	acl,
}

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

	setDescriptionSafe(state, payload) {
		state.descriptionSafe = payload.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
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

	typeIcon: (state) => {
		if (state.type === 'textPoll') {
			return 'icon-md-text-poll'
		}
		return 'icon-md-date-poll'
	},

	answerSequence: (state, getters, rootState) => {
		const noString = rootState.poll.useNo ? 'no' : ''
		if (state.allowMaybe) {
			return [noString, 'yes', 'maybe']
		}
		return [noString, 'yes']

	},

	participants: (state, getters, rootState) => {
		const participants = uniqueParticipants(rootState.votes.list)

		// add current user, if not among participants and voting is allowed
		if (!participants.find((item) => item.userId === state.acl.userId) && state.acl.userId && state.acl.allowVote) {
			participants.push({
				userId: state.acl.userId,
				displayName: state.acl.displayName,
				isNoUser: state.isNoUser,
			})
		}

		return participants
	},

	safeParticipants: (state, getters) => {
		if (getters.safeTable) {
			return [{
				userId: state.acl.userId,
				displayName: state.acl.displayName,
				isNoUser: state.isNoUser,
			}]
		}
		return getters.participants
	},

	participantsVoted: (state, getters, rootState) => uniqueArrayOfObjects(rootState.votes.list.map((item) => ({
		userId: item.userId,
		displayName: item.displayName,
		isNoUser: item.isNoUser,
	}))),

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
	safeTable: (state, getters, rootState) => getters.countCells > rootState.settings.user.performanceThreshold,
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
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = `${endPoint}/s/${context.rootState.route.params.token}/poll`
		} else if (context.rootState.route.name === 'vote') {
			endPoint = `${endPoint}/poll/${context.rootState.route.params.id}/poll`
		} else {
			context.commit('reset')
			context.commit('acl/reset')
			return
		}
		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('set', response.data)
			context.commit('acl/set', response.data)
		} catch (e) {
			console.debug('Error loading poll', { error: e.response })
			throw e
		}
	},

	async add(context, payload) {
		const endPoint = 'apps/polls/poll/add'
		try {
			return await axios.post(generateUrl(endPoint), { title: payload.title, type: payload.type })
		} catch (e) {
			console.error('Error adding poll:', { error: e.response }, { state: context.state })
			throw e
		}
	},

	async clone(context, payload) {
		const endPoint = `apps/polls/poll/${payload.pollId}/clone`
		try {
			return await axios.get(generateUrl(endPoint))
		} catch (e) {
			console.error('Error cloning poll', { error: e.response }, { payload })
		}
	},

	async update(context) {
		const endPoint = `apps/polls/poll/${context.state.id}`
		try {
			const response = await axios.put(generateUrl(endPoint), { poll: context.state })
			context.commit('set', { poll: response.data })
		} catch (e) {
			console.error('Error updating poll:', { error: e.response }, { poll: context.state })
			throw e
		}
	},

	async toggleArchive(context, payload) {
		const endPoint = `apps/polls/poll/${payload.pollId}/toggleArchive`
		try {
			await axios.put(generateUrl(endPoint))
		} catch (e) {
			console.error('Error archiving/restoring', { error: e.response }, { payload })
		}
	},

	async delete(context, payload) {
		const endPoint = `apps/polls/poll/${payload.pollId}`
		try {
			await axios.delete(generateUrl(endPoint))
		} catch (e) {
			console.error('Error deleting poll', { error: e.response }, { payload })
		}
	},

	async getParticipantsEmailAddresses(context) {
		const endPoint = `apps/polls/poll/${context.state.id}/addresses`
		try {
			return await axios.get(generateUrl(endPoint))
		} catch (e) {
			console.error('Error retrieving email addresses', { error: e.response })
		}
	},

}

export default { namespaced, state, mutations, getters, actions, modules }
