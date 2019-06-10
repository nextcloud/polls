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
 import Vue from 'vue'

 const state = {
	poll: {
		comments: [],
		event: {
			id: 0,
			hash: '',
			type: 'datePoll',
			title: '',
			description: '',
			created: '',
			access: 'public',
			expiration: false,
			expirationDate: '',
			expired: false,
			isAnonymous: false,
			fullAnonymous: false,
			allowMaybe: false,
			owner: undefined
		},
		grantedAs: 'owner',
		id: 0,
		mode: 'vote',
		participants: [],
		result: 'new',
		shares: [],
		voteOptions: [],
		votes: []
	}
}

const mutations = {
 	setPoll(state , payload ) {
		state.poll = payload.poll
	},
	resetPoll(state) {
		state.poll = {}
	}
}

const getters = {
	lastVoteId( state ) {
		return Math.max.apply(Math, state.poll.votes.map(function(o) { return o.id }))
	},

	timeSpanCreated( state ) {
		return moment(state.poll.event.created).fromNow()
	},

	timeSpanExpiration(state) {
		if (state.poll.event.expiration) {
			return moment(state.poll.event.expirationDate).fromNow()
		} else {
			return t('polls', 'never')
		}
	},

	optionsVotes() {
		var votesList = []

		state.poll.voteOptions.forEach(function(option) {
			votesList.push(
				{
					option: option.id,
					votes: state.poll.votes.filter(obj => {
						return obj.voteOptionText === option.text
					}),
				}
			)
		})
		return votesList
	},

	sentences(state) {
		return {
			'countComments': n('polls', 'There is %n comment', 'There are %n comments', state.poll.comments.length),
			'countParticipants': n('polls', 'This poll has %n participant', 'This poll has %n participants', state.poll.participants.length)
		}
	},

	adminMode(state) {
		return (state.poll.event.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
	},

	accessType(state) {
		if (state.poll.event.access === 'public') {
			return t('polls', 'Public access')
		} else if (state.poll.event.access === 'select') {
			return t('polls', 'Only shared')
		} else if (state.poll.event.access === 'registered') {
			return t('polls', 'Registered users only')
		} else if (state.poll.event.access === 'hidden') {
			return t('polls', 'Hidden poll')
		} else {
			return ''
		}
	},

	participantsVotes(state) {
		var votesList = []
		var thisPoll = state.poll
		var templist = []
		var foundVote = []
		var fakeVoteId = 78946456

		state.poll.participants.forEach(function(participant) {
			templist = []
			state.poll.voteOptions.forEach(function(voteOption) {
				foundVote = state.poll.votes.filter(obj => {
					return obj.userId === participant && obj.voteOptionText === voteOption.text
				})

				if (foundVote.length > 0) {
					templist.push(foundVote[0])
				} else {
					templist.push({
						id: ++fakeVoteId,
						userId: participant,
						voteAnswer: 'unvoted',
						voteOptionText: voteOption.text,
						voteOptionId: voteOption.id
					})
				}
			})

			votesList.push(
				{
					name: participant,
					votes: templist
				}
			)
		})
		return votesList
	},

}

const actions = {
 	loadPoll({ commit }, payload) {
		axios.get(OC.generateUrl('apps/polls/get/poll/' + payload.hash))

			.then((response) => {
				commit({ type: 'setPoll', poll: response.data })

			}, (error) => {
				/* eslint-disable-next-line no-console */
				console.log(error)
				commit('resetPoll')
			}
		)
 	}
 }

 export default { state, mutations, getters, actions }
