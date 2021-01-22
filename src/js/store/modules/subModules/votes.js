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
import orderBy from 'lodash/orderBy'
import { generateUrl } from '@nextcloud/router'

const defaultVotes = () => {
	return {
		list: [],
	}
}

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
		state.list = state.list.filter(vote => vote.userId !== payload.userId)
	},

	setItem(state, payload) {
		const index = state.list.findIndex(vote =>
			parseInt(vote.pollId) === payload.pollId
			&& vote.userId === payload.vote.userId
			&& vote.voteOptionText === payload.option.pollOptionText)
		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.vote)
		} else {
			state.list.push(payload.vote)
		}
	},
}

const getters = {

	relevant: (state, getters, rootState) => {
		return state.list.filter((vote) => {
			return rootState.poll.options.list.some((option) => {
				return option.pollId === vote.pollId && option.pollOptionText === vote.voteOptionText
			})
		})
	},

	ranked: (state, getters, rootState) => {
		let votesRank = []
		rootState.poll.options.list.forEach(function(option) {
			const countYes = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'yes').length
			const countMaybe = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'maybe').length
			const countNo = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'no').length
			votesRank.push({
				rank: 0,
				pollOptionText: option.pollOptionText,
				yes: countYes,
				no: countNo,
				maybe: countMaybe,
			})
		})
		votesRank = orderBy(votesRank, ['yes', 'maybe'], ['desc', 'desc'])
		for (let i = 0; i < votesRank.length; i++) {
			if (i > 0 && votesRank[i].yes === votesRank[i - 1].yes && votesRank[i].maybe === votesRank[i - 1].maybe) {
				votesRank[i].rank = votesRank[i - 1].rank
			} else {
				votesRank[i].rank = i + 1
			}
		}
		return votesRank
	},

	countYesVotes: (state, getters, rootState) => {
		return getters.relevant.filter(vote => vote.userId === rootState.poll.acl.userId && vote.voteAnswer === 'yes').length
	},

	getVote: (state) => (payload) => {
		const found = state.list.find(vote => {
			return (vote.userId === payload.userId
				&& vote.voteOptionText === payload.option.pollOptionText)
		})
		if (found === undefined) {
			return {
				voteAnswer: '',
				voteOptionText: payload.option.pollOptionText,
				userId: payload.userId,
			}
		} else {
			return found
		}
	},
}

const actions = {
	list(context, payload) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else if (context.rootState.route.name === 'vote') {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		} else {
			context.commit('reset')
			return
		}

		return axios.get(generateUrl(endPoint + '/votes'))
			.then((response) => {
				context.commit('set', response.data)
			})
			.catch(() => {
				context.commit('reset')
			})

	},
	set(context, payload) {
		let endPoint = 'apps/polls'

		if (context.rootState.poll.acl.token) {
			endPoint = endPoint + '/s/' + context.rootState.poll.acl.token
		}

		return axios.put(generateUrl(endPoint + '/vote'), {
			optionId: payload.option.id,
			setTo: payload.setTo,
		})
			.then((response) => {
				context.commit('setItem', { option: payload.option, pollId: context.rootState.poll.id, vote: response.data.vote })
				return response.data
			})
			.catch((error) => {
				if (error.response.status === 409) {
					context.dispatch('list')
				} else {
					console.error('Error setting vote', { error: error.response }, { payload: payload })
					throw error
				}
			})
	},

	deleteUser(context, payload) {
		const endPoint = 'apps/polls/poll/' + context.rootState.route.params.id + '/user/' + payload.userId
		return axios.delete(generateUrl(endPoint))
			.then(() => {
				context.commit('deleteVotes', payload)
			})
			.catch((error) => {
				console.error('Error deleting votes', { error: error.response }, { payload: payload })
				throw error
			})
	},

}

export default { namespaced, state, mutations, getters, actions }
