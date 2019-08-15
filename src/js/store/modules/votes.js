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

const defaultVotes = () => {
	return {
		list: []
	}
}

const state = defaultVotes()

const mutations = {
	votesSet(state, payload) {
		Object.assign(state, payload)
	},

	votesReset(state) {
		Object.assign(state, defaultVotes())
	},

	voteSet(state, payload) {
		var index = state.list.findIndex(vote =>
			vote.pollId === payload.pollId
			&& vote.userId === payload.newVote.userId
			&& vote.voteOptionText === payload.option.text)
		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.newVote)
		} else {
			state.list.push(payload.newVote)
		}
	}
}

const getters = {
	lastVoteId: state => {
		return Math.max.apply(Math, state.list.map(function(o) { return o.id }))
	},

	participants: (state, getters, rootState) => {
		var list = []
		state.list.forEach(function(vote) {
			if (!list.includes(vote.userId)) {
				list.push(vote.userId)
			}
		})

		if (!list.includes(rootState.poll.currentUser)) {
			list.push(rootState.poll.currentUser)
		}

		return list
	},

	countParticipants: (state, getters) => {
		return getters.participants.length
	},

	usersVotes: (state, getters) => (userId) => {
		return getters.allVotes.filter(vote => {
			return vote.userId === userId
		})
	},

	getAnswer: (state, getters, rootState) => (payload) => {
		var index = state.list.findIndex(vote =>
			vote.pollId === rootState.event.id
			&& vote.userId === payload.userId
			&& vote.voteOptionText === payload.option.text)
		if (index > -1) {
			return state.list[index].voteAnswer
		} else {
			return 'unvoted'
		}
	}

}

const actions = {

	loadVotes({ commit, rootState }, payload) {
		axios.get(OC.generateUrl('apps/polls/get/votes/' + payload.pollId))
			.then((response) => {
				commit('votesSet', {
					'list': response.data
				})
			}, (error) => {
				commit({ type: 'votesReset' })
				/* eslint-disable-next-line no-console */
				console.log(error)
			})
	},

	voteChange({ commit, rootState }, payload) {
		return axios.post(OC.generateUrl('apps/polls/set/vote'), {
			pollId: rootState.event.id,
			option: payload.option,
			userId: payload.userId,
			setTo: payload.switchTo
		})
			.then((response) => {
				commit('voteSet', { option: payload.option, pollId: rootState.event.id, newVote: response.data })
				return response.data
			}, (error) => {
			/* eslint-disable-next-line no-console */
				console.log(error.response)
			})
	}

}

export default { state, mutations, getters, actions }
