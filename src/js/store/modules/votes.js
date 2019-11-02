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

import axios from 'nextcloud-axios'
import orderBy from 'lodash/orderBy'

const defaultVotes = () => {
	return {
		currentUser: OC.getCurrentUser().uid,
		list: []
	}
}

const state = defaultVotes()

const mutations = {
	resetVotes(state) {
		Object.assign(state, defaultVotes())
	},

	setVotes(state, payload) {
		Object.assign(state, payload)
	},

	setVote(state, payload) {
		var index = state.list.findIndex(vote =>
			parseInt(vote.pollId) === payload.pollId
			&& vote.userId === payload.vote.userId
			&& vote.voteOptionText === payload.option.pollOptionText)
		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.vote)
		} else {
			state.list.push(payload.vote)
		}
	}
}

const getters = {

	lastVoteId: state => {
		return Math.max.apply(Math, state.list.map(function(o) { return o.id }))
	},

	answerSequence: (state, getters, rootState) => {
		if (rootState.event.allowMaybe) {
			return ['no', 'maybe', 'yes', 'no']
		} else {
			return ['no', 'yes', 'no']
		}
	},

	participants: (state) => {
		var list = []
		state.list.forEach(function(vote) {
			if (!list.includes(vote.userId)) {
				list.push(vote.userId)
			}
		})

		if (!list.includes(state.currentUser)) {
			list.push(state.currentUser)
		}

		return list
	},

	countParticipants: (state, getters) => {
		return getters.participants.length
	},

	votesRank: (state, getters, rootGetters) => {
		var rank = []
		rootGetters.options.list.forEach(function(option) {
			var countYes = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'yes').length
			var countMaybe = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'maybe').length
			var countNo = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'no').length
			rank.push({
				'rank': 0,
				'pollOptionText': option.pollOptionText,
				'yes': countYes,
				'no': countNo,
				'maybe': countMaybe
			})
		})
		return orderBy(rank, ['yes', 'maybe'], ['desc', 'desc'])
	},

	winnerCombo: (state, getters) => {
		return getters.votesRank[0]
	},

	getVote: (state, getters) => (payload) => {
		return state.list.find(vote => {
			return (vote.userId === payload.userId
				&& vote.voteOptionText === payload.option.pollOptionText)
		})
	},

	getNextAnswer: (state, getters) => (payload) => {
		try {
			return getters.answerSequence[getters.answerSequence.indexOf(getters.getVote(payload).voteAnswer) + 1]
		} catch (e) {
			return getters.answerSequence[1]
		}

	}

}

const actions = {

	loadPoll({ commit, rootState }, payload) {
		axios.get(OC.generateUrl('apps/polls/get/votes/' + payload.pollId))
			.then((response) => {
				commit('setVotes', {
					'list': response.data
				})
			}, (error) => {
				commit({ type: 'resetVotes' })
				console.error(error)
			})
	},

	setVoteAsync({ commit, getters, rootState }, payload) {

		return axios.post(OC.generateUrl('apps/polls/set/vote'), {
			pollId: rootState.event.id,
			option: payload.option,
			userId: payload.userId,
			setTo: payload.setTo
		})
			.then((response) => {
				commit('setVote', { option: payload.option, pollId: rootState.event.id, vote: response.data })
				return response.data
			}, (error) => {
				console.error(error.response)
			})
	}

}

export default { state, mutations, getters, actions }
