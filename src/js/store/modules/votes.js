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

const defaultVotes = () => {
	return {
		list: []
	}
}

const state = defaultVotes()

const mutations = {
	reset(state) {
		Object.assign(state, defaultVotes())
	},

	setVotes(state, payload) {
		Object.assign(state, payload)
	},

	setVote(state, payload) {
		let index = state.list.findIndex(vote =>
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

	answerSequence: (state, getters, rootState) => {
		if (rootState.event.allowMaybe) {
			return ['no', 'maybe', 'yes', 'no']
		} else {
			return ['no', 'yes', 'no']
		}
	},

	participants: (state, getters, rootState) => {
		let list = []
		state.list.forEach(function(vote) {
			if (!list.includes(vote.userId)) {
				list.push(vote.userId)
			}
		})

		if (!list.includes(rootState.acl.userId) && rootState.acl.userId !== null) {
			list.push(rootState.acl.userId)
		}

		return list
	},

	votesRank: (state, getters, rootGetters) => {
		let rank = []
		rootGetters.options.list.forEach(function(option) {
			let countYes = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'yes').length
			let countMaybe = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'maybe').length
			let countNo = state.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'no').length
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
		commit('reset')
		let endPoint = 'apps/polls/votes/get/'
		if (payload.token !== undefined) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			return
		}

		axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				commit('setVotes', { 'list': response.data })
			}, (error) => {
				console.error('Error loading votes', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	},

	setVoteAsync({ commit, getters, rootState }, payload) {

		let endPoint = 'apps/polls/set/vote/'

		if (rootState.acl.foundByToken) {
			endPoint = endPoint.concat('s/')
		}

		return axios.post(OC.generateUrl(endPoint), {
			pollId: rootState.event.id,
			token: rootState.acl.token,
			option: payload.option,
			userId: payload.userId,
			setTo: payload.setTo
		})
			.then((response) => {
				commit('setVote', { option: payload.option, pollId: rootState.event.id, vote: response.data })
				return response.data
			}, (error) => {
				console.error('Error setting vote', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	}

}

export default { state, mutations, getters, actions }
