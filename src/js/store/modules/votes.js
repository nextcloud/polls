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
		votes: [],
	}
}

const state = defaultVotes()

const mutations = {
	set(state, payload) {
		state.votes = payload.votes
	},

	reset(state) {
		Object.assign(state, defaultVotes())
	},

	deleteVotes(state, payload) {
		state.votes = state.votes.filter(vote => vote.userId !== payload.userId)
	},

	setVote(state, payload) {
		const index = state.votes.findIndex(vote =>
			parseInt(vote.pollId) === payload.pollId
			&& vote.userId === payload.vote.userId
			&& vote.voteOptionText === payload.option.pollOptionText)
		if (index > -1) {
			state.votes[index] = Object.assign(state.votes[index], payload.vote)
		} else {
			state.votes.push(payload.vote)
		}
	},
}

const getters = {

	answerSequence: (state, getters, rootState) => {
		if (rootState.poll.allowMaybe) {
			return ['no', 'maybe', 'yes', 'no']
		} else {
			return ['no', 'yes', 'no']
		}
	},

	participantsVoted: (state, getters) => {
		const participantsVoted = []
		const map = new Map()
		for (const item of state.votes) {
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

	participants: (state, getters, rootState) => {
		const participants = []
		const map = new Map()
		for (const item of state.votes) {
			if (!map.has(item.userId)) {
				map.set(item.userId, true)
				participants.push({
					userId: item.userId,
					displayName: item.displayName,
					voted: true,
				})
			}
		}

		if (!map.has(rootState.acl.userId) && rootState.acl.userId && rootState.acl.allowVote) {
			participants.push({
				userId: rootState.acl.userId,
				displayName: rootState.acl.displayName,
				voted: false,
			})
		}
		return participants
	},

	votesRank: (state, getters, rootGetters) => {
		let votesRank = []
		rootGetters.options.options.forEach(function(option) {
			const countYes = state.votes.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'yes').length
			const countMaybe = state.votes.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'maybe').length
			const countNo = state.votes.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'no').length
			votesRank.push({
				rank: 0,
				pollOptionText: option.pollOptionText,
				yes: countYes,
				no: countNo,
				maybe: countMaybe,
			})
		})
		votesRank = orderBy(votesRank, ['yes', 'maybe'], ['desc', 'desc'])
		for (var i = 0; i < votesRank.length; i++) {
			if (i > 0 && votesRank[i].yes === votesRank[i - 1].yes && votesRank[i].maybe === votesRank[i - 1].maybe) {
				votesRank[i].rank = votesRank[i - 1].rank
			} else {
				votesRank[i].rank = i + 1
			}
		}
		return votesRank
	},

	winnerCombo: (state, getters) => {
		return getters.votesRank[0]
	},

	getVote: (state) => (payload) => {
		return state.votes.find(vote => {
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

	},

}

const actions = {
	deleteVotes(context, payload) {
		const endPoint = 'apps/polls/votes/delete/'
		return axios.post(generateUrl(endPoint), {
			pollId: context.rootState.poll.id,
			voteId: 0,
			userId: payload.userId,
		})
			.then(() => {
				context.commit('deleteVotes', payload)
				OC.Notification.showTemporary(t('polls', 'User {userId} removed', payload), { type: 'success' })
			}, (error) => {
				console.error('Error deleting votes', { error: error.response }, { payload: payload })
				throw error
			})
	},

	setVoteAsync(context, payload) {
		let endPoint = 'apps/polls/vote/set/'

		if (context.rootState.acl.foundByToken) {
			endPoint = endPoint.concat('s/')
		}

		return axios.post(generateUrl(endPoint), {
			pollId: context.rootState.poll.id,
			token: context.rootState.acl.token,
			option: payload.option,
			userId: payload.userId,
			setTo: payload.setTo,
		})
			.then((response) => {
				context.commit('setVote', { option: payload.option, pollId: context.rootState.poll.id, vote: response.data })
				return response.data
			}, (error) => {
				console.error('Error setting vote', { error: error.response }, { payload: payload })
				throw error
			})
	},

}

export default { state, mutations, getters, actions }
