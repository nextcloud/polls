/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
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
import { generateUrl } from '@nextcloud/router'

const defaultVotes = () => ({
	list: [],
})

const state = defaultVotes()

const namespaced = true

const mutations = {
	set(state, payload) {
		state.list = payload.votes
	},

	reset(state) {
		Object.assign(state, defaultVotes())
	},

	deleteVotes(state, payload) {
		state.list = state.list.filter((vote) => vote.userId !== payload.userId)
	},

	setItem(state, payload) {
		const index = state.list.findIndex((vote) =>
			parseInt(vote.pollId) === payload.pollId
			&& vote.userId === payload.vote.userId
			&& vote.voteOptionText === payload.option.pollOptionText)
		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.vote)
			return
		}

		state.list.push(payload.vote)

		// TODO: performance check for preferred strategy
		// for (let i = 0; i < state.list.length; i++) {
		// if (parseInt(state.list[i].pollId) === payload.pollId
		// && state.list[i].userId === payload.vote.userId
		// && state.list[i].voteOptionText === payload.option.pollOptionText) {
		// state.list[i] = Object.assign(state.list[i], payload.vote)
		// return
		// }
		// }
		// state.list.push(payload.vote)
	},
}

const getters = {

	relevant: (state, getters, rootState) => state.list.filter((vote) => rootState.options.list.some((option) => option.pollId === vote.pollId && option.pollOptionText === vote.voteOptionText)),

	countVotes: (state, getters, rootState) => (answer) => getters.relevant.filter((vote) => vote.userId === rootState.poll.acl.userId && vote.voteAnswer === answer).length,

	countAllVotes: (state, getters, rootState) => (answer) => getters.relevant.filter((vote) => vote.voteAnswer === answer).length,

	getVote: (state) => (payload) => {
		const found = state.list.find((vote) => (vote.userId === payload.userId
				&& vote.voteOptionText === payload.option.pollOptionText))
		if (found === undefined) {
			return {
				voteAnswer: '',
				voteOptionText: payload.option.pollOptionText,
				userId: payload.userId,
			}
		}
		return found

	},
}

const actions = {
	async list(context) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else if (context.rootState.route.name === 'vote') {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		} else {
			context.commit('reset')
			return
		}
		try {
			const response = await axios.get(generateUrl(endPoint + '/votes'), { params: { time: +new Date() } })
			context.commit('set', response.data)
		} catch {
			context.commit('reset')
		}
	},

	async set(context, payload) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.poll.acl.token
		}

		try {
			const response = await axios.put(generateUrl(endPoint + '/vote'), {
				optionId: payload.option.id,
				setTo: payload.setTo,
			})
			context.commit('setItem', { option: payload.option, pollId: context.rootState.poll.id, vote: response.data.vote })
			context.dispatch('options/list', null, { root: true })
			return response
		} catch (e) {
			if (e.response.status === 409) {
				context.dispatch('list')
				context.dispatch('options/list', null, { root: true })
			} else {
				console.error('Error setting vote', { error: e.response }, { payload })
				throw e
			}
		}
	},

	async resetVotes(context) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.poll.acl.token + '/user'
		} else {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id + '/user'
		}

		try {
			const response = await axios.delete(generateUrl(endPoint))
			context.commit('deleteVotes', { userId: response.data.deleted })
		} catch (e) {
			console.error('Error deleting votes', { error: e.response })
			throw e
		}
	},

	async deleteUser(context, payload) {
		const endPoint = 'apps/polls/poll/' + context.rootState.route.params.id + '/user/' + payload.userId
		try {
			await axios.delete(generateUrl(endPoint))
			context.commit('deleteVotes', payload)
		} catch (e) {
			console.error('Error deleting votes', { error: e.response }, { payload })
			throw e
		}
	},

}

export default { namespaced, state, mutations, getters, actions }
