/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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

const defaultPoll = () => {
	return {
		id: 0,
		type: 'datePoll',
		title: '',
		description: '',
		owner: '',
		created: 0,
		expire: 0,
		deleted: 0,
		access: 'hidden',
		anonymous: 0,
		allowComment: 0,
		allowMaybe: 0,
		voteLimit: 0,
		optionLimit: 0,
		showResults: 'always',
		adminAccess: 0,
		important: 0,
		hideBookedUp: 0,
	}
}

const state = defaultPoll()

const namespaced = true
const modules = {
	acl: acl,
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

}

const getters = {
	answerSequence: (state) => {
		if (state.allowMaybe) {
			return ['no', 'yes', 'maybe']
		} else {
			return ['no', 'yes']
		}
	},

	closed: (state) => {
		return (state.expire > 0 && moment.unix(state.expire).diff() < 0)
	},

	participants: (state, getters, rootState) => {
		const participants = []
		const map = new Map()
		for (const item of rootState.votes.list) {
			if (!map.has(item.userId)) {
				map.set(item.userId, true)
				participants.push({
					userId: item.userId,
					displayName: item.displayName,
					isNoUser: item.isNoUser,
					voted: true,
				})
			}
		}

		if (!map.has(state.acl.userId) && state.acl.userId && state.acl.allowVote) {
			participants.push({
				userId: state.acl.userId,
				displayName: state.acl.displayName,
				isNoUser: state.isNoUser,
				voted: false,
			})
		}
		return participants
	},

	participantsVoted: (state, getters, rootState) => {
		const participantsVoted = []
		const map = new Map()
		for (const item of rootState.votes.list) {
			if (!map.has(item.userId)) {
				map.set(item.userId, true)
				participantsVoted.push({
					userId: item.userId,
					displayName: item.displayName,
					isNoUser: item.isNoUser,
				})
			}
		}
		return participantsVoted
	},
}

const actions = {

	reset(context) {
		context.commit('reset')
	},

	async get(context) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else if (context.rootState.route.name === 'vote') {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		} else {
			context.commit('reset')
			context.commit('acl/reset')
			return
		}
		try {
			const response = await axios.get(generateUrl(endPoint + '/poll'))
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
			console.error('Error adding poll:', { error: e.response }, { state: state })
			throw e
		}
	},

	async clone(context, payload) {
		const endPoint = 'apps/polls/poll'
		try {
			return await axios.get(generateUrl(endPoint + '/' + payload.pollId + '/clone'))
		} catch (e) {
			console.error('Error cloning poll', { error: e.response }, { payload: payload })
		}
	},

	async update(context) {
		const endPoint = 'apps/polls/poll'
		try {
			const response = await axios.put(generateUrl(endPoint + '/' + state.id), { poll: state })
			context.commit('set', { poll: response.data })
		} catch (e) {
			console.error('Error updating poll:', { error: e.response }, { poll: state })
			throw e
		}
	},

	async switchDeleted(context, payload) {
		const endPoint = 'apps/polls/poll'
		try {
			await axios.put(generateUrl(endPoint + '/' + payload.pollId + '/switchDeleted'))
		} catch (e) {
			console.error('Error switching deleted status', { error: e.response }, { payload: payload })
		}
	},

	async delete(context, payload) {
		const endPoint = 'apps/polls/poll'
		try {
			await axios.delete(generateUrl(endPoint + '/' + payload.pollId))
		} catch (e) {
			console.error('Error deleting poll', { error: e.response }, { payload: payload })
		}
	},

	async getParticipantsEmailAddresses(context) {
		const endPoint = 'apps/polls/poll'
		try {
			return await axios.get(generateUrl(endPoint + '/' + state.id + '/addresses'))
		} catch (e) {
			console.error('Error retrieving email addresses', { error: e.response })
		}
	},

}

export default { namespaced, state, mutations, getters, actions, modules }
