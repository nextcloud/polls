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

import axios from 'nextcloud-axios'
import sortBy from 'lodash/sortBy'


const defaultVotes = () => {
	return {
		currentUser: '',
		list: [],
		pollId: 0,
		voteChanged: false
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

	// TODO: Find a better solution than this
	voteChange(state, payload) {
		state.list.forEach(function(vote) {
			if (vote === payload.payload) {
				vote.voteAnswer = payload.switchTo
				vote.voteOptionId = payload.payload.id
				state.votechanged = true
			}
		})
	}
}

const getters = {
	allVotes: (state, getters, rootState) => {
		var votesList = []
		var foundVote = []
		var fakeVoteId = 78946456

		getters.participants.forEach(function(participant) {
			rootState.options.list.forEach(function(voteOption) {
				foundVote = state.list.filter(vote => {
					return vote.userId === participant && vote.voteOptionText === voteOption.text
				})

				if (foundVote.length > 0) {
					votesList.push(foundVote[0])
				} else {
					votesList.push({
						id: ++fakeVoteId,
						userId: participant,
						voteAnswer: 'unvoted',
						voteOptionText: voteOption.text,
						voteOptionId: voteOption.id
					})
				}
			})
		})
		if (rootState.event.type === 'datePoll') {
			return sortBy(votesList, 'voteOptionText')
		} else {
			return votesList
		}
	},

	lastVoteId: state => {
		return Math.max.apply(Math, state.list.map(function(o) { return o.id }))
	},

	participants: state => {
		var list = []

		state.list.forEach(function(vote) {
			if (!list.includes(vote.userId)) {
				list.push(vote.userId)
			}
		})
		return list
	},

	countParticipants: (state, getters) => {
		return getters.participants.length
	},

	currentUserParticipated: (state, getters) => {
		return getters.participants.includes(state.currentUser)
	},

	usersVotes: (state, getters) => (userId) => {
		return getters.allVotes.filter(vote => {
			return vote.userId === userId
		})
	},

}

const actions = {

	loadVotes({ commit }, payload) {
		commit({ type: 'votesReset' })
		axios.get(OC.generateUrl('apps/polls/get/votes/' + payload.pollId))
			.then((response) => {
				commit('votesSet', {
					'list': response.data,
					'currentUser': payload.currentUser ,
					'pollId': payload.pollId
				})
			}, (error) => {
				commit({ type: 'votesReset' })
			/* eslint-disable-next-line no-console */
				console.log(error)
			})
	},

	writeVotesPromise({ commit }, payload) {
		return
		return axios.post(OC.generateUrl('apps/polls/write/vote'), { pollId: state.pollId, votes: payload.votes, currentUser: state.currentUser })
		.then((response) => {
			commit('votesSet', {
				'list': response.data,
				'currentUser': payload.currentUser ,
				'pollId': payload.pollId
			})

		}, (error) => {
			/* eslint-disable-next-line no-console */
			console.log(error.response)
		})
	},

}

export default { state, mutations, getters, actions }
