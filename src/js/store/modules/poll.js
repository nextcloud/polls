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
import comments from './subModules/comments.js'
import options from './subModules/options.js'
import shares from './subModules/shares.js'
import votes from './subModules/votes.js'

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
		allowMaybe: 0,
		voteLimit: 0,
		showResults: 'always',
		adminAccess: 0,
	}
}

const state = defaultPoll()

const namespaced = true
const modules = {
	acl: acl,
	comments: comments,
	options: options,
	shares: shares,
	votes: votes,
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

	expired: (state) => {
		return (state.expire > 0 && moment.unix(state.expire).diff() < 0)
	},

	participants: (state, getters) => {
		const participants = []
		const map = new Map()
		for (const item of state.votes.list) {
			if (!map.has(item.userId)) {
				map.set(item.userId, true)
				participants.push({
					userId: item.userId,
					displayName: item.displayName,
					voted: true,
				})
			}
		}

		if (!map.has(state.acl.userId) && state.acl.userId && state.acl.allowVote) {
			participants.push({
				userId: state.acl.userId,
				displayName: state.acl.displayName,
				voted: false,
			})
		}
		return participants
	},

	participantsVoted: (state, getters) => {
		const participantsVoted = []
		const map = new Map()
		for (const item of state.votes.list) {
			if (!map.has(item.userId)) {
				map.set(item.userId, true)
				participantsVoted.push({
					userId: item.userId,
					displayName: item.displayName,
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

	get(context, payload) {
		let endPoint = 'apps/polls/polls/get'
		if (payload.token) {
			endPoint = endPoint.concat('/s/', payload.token)
		} else if (payload.pollId) {
			endPoint = endPoint.concat('/', payload.pollId)
		} else {
			context.commit('reset')
			context.commit('acl/reset')
			context.commit('comments/reset')
			context.commit('options/reset')
			context.commit('shares/reset')
			context.commit('votes/reset')
			return
		}
		return axios.get(generateUrl(endPoint))
			.then((response) => {
				context.commit('set', response.data)
				context.commit('acl/set', response.data)
				context.commit('comments/set', response.data)
				context.commit('options/set', response.data)
				context.commit('shares/set', response.data)
				context.commit('votes/set', response.data)
				return response
			}, (error) => {
				if (error.response.status !== '404' && error.response.status !== '401') {
					console.debug('Error loading poll', { error: error.response }, { payload: payload })
					return error.response
				}
				throw error
			})
	},

	add(context, payload) {
		const endPoint = 'apps/polls/polls/add'
		return axios.post(generateUrl(endPoint), { title: payload.title, type: payload.type })
			.then((response) => {
				return response
			})
			.catch((error) => {
				console.error('Error adding poll:', { error: error.response }, { state: state })
				throw error
			})

	},

	clone(context, payload) {
		const endPoint = 'apps/polls/polls/clone'
		return axios.get(generateUrl(endPoint.concat('/', payload.pollId)))
			.then((response) => {
				return response.data
			})
			.catch((error) => {
				console.error('Error cloning poll', { error: error.response }, { payload: payload })
			})

	},

	update(context) {
		const endPoint = 'apps/polls/polls/update'
		return axios.put(generateUrl(endPoint.concat('/', state.id)), { poll: state })
			.then((response) => {
				context.commit('set', { poll: response.data })
				return response
			})
			.catch((error) => {
				console.error('Error updating poll:', { error: error.response }, { poll: state })
				throw error
			})

	},

	switchDeleted(context, payload) {
		const endPoint = 'apps/polls/polls/delete'
		return axios.get(generateUrl(endPoint.concat('/', payload.pollId)))
			.then((response) => {
				return response
			})
			.catch((error) => {
				console.error('Error deleting poll', { error: error.response }, { payload: payload })
			})
	},

	delete(context, payload) {
		const endPoint = 'apps/polls/polls/delete'
		return axios.get(generateUrl(endPoint.concat('/permanent/', payload.pollId)))
			.then((response) => {
				return response
			})
			.catch((error) => {
				console.error('Error deleting poll', { error: error.response }, { payload: payload })
			})
	},

}

export default { namespaced, state, mutations, getters, actions, modules }
