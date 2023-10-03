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

import { PublicAPI, VotesAPI } from '../../Api/index.js'

const defaultVotes = () => ({
	list: [],
})

const namespaced = true
const state = defaultVotes()

const mutations = {
	set(state, payload) {
		state.list = payload
	},

	reset(state) {
		Object.assign(state, defaultVotes())
	},

	deleteVotes(state, payload) {
		state.list = state.list.filter((vote) => vote.user.userId !== payload.userId)
	},

	setItem(state, payload) {
		const index = state.list.findIndex((vote) =>
			parseInt(vote.pollId) === payload.pollId
			&& vote.user.userId === payload.vote.user.userId
			&& vote.optionText === payload.option.text)
		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.vote)
			return
		}
		state.list.push(payload.vote)
	},
}

const getters = {
	relevant: (state, getters, rootState) => state.list.filter((vote) => rootState.options.list.some((option) => option.pollId === vote.pollId && option.text === vote.optionText)),
	countVotes: (state, getters, rootState) => (answer) => getters.relevant.filter((vote) => vote.user.userId === rootState.poll.acl.userId && vote.answer === answer).length,
	countAllVotes: (state, getters) => (answer) => getters.relevant.filter((vote) => vote.answer === answer).length,
	hasVotes: (state) => state.list.length > 0,

	getVote: (state) => (payload) => {
		const found = state.list.find((vote) => (vote.user.userId === payload.userId
				&& vote.optionText === payload.option.text))
		if (found === undefined) {
			return {
				answer: '',
				optionText: payload.option.text,
				userId: payload.userId,
			}
		}
		return found
	},
}

const actions = {
	async list(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getVotes(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await VotesAPI.getVotes(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}

			const votes = []
			response.data.votes.forEach((vote) => {
				if (vote.answer === 'yes') {
					vote.answerTranslated = t('polls', 'Yes')
					vote.answerSymbol = '✔'
				} else if (vote.answer === 'maybe') {
					vote.answerTranslated = t('polls', 'Maybe')
					vote.answerSymbol = '❔'
				} else {
					vote.answerTranslated = t('polls', 'No')
					vote.answerSymbol = '❌'
				}
				votes.push(vote)
			})
			context.commit('set', votes)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			context.commit('reset')
			throw e
		}
	},

	async set(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.setVote(context.rootState.route.params.token, payload.option.id, payload.setTo)
			} else {
				response = await VotesAPI.setVote(payload.option.id, payload.setTo)
			}
			context.commit('setItem', { option: payload.option, pollId: context.rootState.poll.id, vote: response.data.vote })
			context.dispatch('options/list', null, { root: true })
			context.dispatch('poll/get', null, { root: true })
			return response
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
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
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.removeVotes(context.rootState.route.params.token)
			} else {
				response = await VotesAPI.removeUser(context.rootState.route.params.id)
			}
			context.commit('deleteVotes', { userId: response.data.deleted })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error deleting votes', { error: e.response })
			throw e
		}
	},

	async deleteUser(context, payload) {
		try {
			await VotesAPI.removeUser(context.rootState.route.params.id, payload.userId)
			context.commit('deleteVotes', payload)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error deleting votes', { error: e.response }, { payload })
			throw e
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
