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
import moment from 'moment'

const defaultPoll = () => {
	return {
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
		mode: 'create',
		participants: [],
		result: 'new',
		shares: [],
		currentUser: '',
		voteOptions: [],
		votes: []
	}
}

const state = defaultPoll()

const mutations = {
	setPoll(state, payload) {
		Object.assign(state, payload.poll)
	},

	resetPoll(state) {
		Object.assign(state, defaultPoll())
	},

	setPollProperty(state, payload) {
		state[payload.property] = payload.value
	},

	setEventProperty(state, payload) {
		state.event[payload.property] = payload.value
	},

	addDate(state, payload) {
		state.voteOptions.push({
			id: 0,
			timestamp: moment(payload).unix(),
			text: moment.utc(payload).format('YYYY-MM-DD HH:mm:ss')
		})
	},

	addText(state, payload) {
		state.voteOptions.push({
			id: 0,
			text: payload,
			timestamp: 0
		})
	},

	shiftDates(state, payload) {
		state.voteOptions.forEach(function(option) {
			option.text = moment(option.text).add(payload.step, payload.unit).format('YYYY-MM-DD HH:mm:ss')
			option.timestamp = moment.utc(option.text).unix()
		})
	},

	removeDate(state, payload) {
	}

}

const getters = {
	lastVoteId(state) {
		return Math.max.apply(Math, state.votes.map(function(o) { return o.id }))
	},

	timeSpanCreated(state) {
		return moment(state.event.created).fromNow()
	},

	sortedDates(state) {
		return sortBy(state.voteOptions, 'timestamp')
	},

	timeSpanExpiration(state) {
		if (state.event.expiration) {
			return moment(state.event.expirationDate).fromNow()
		} else {
			return t('polls', 'never')
		}
	},

	optionsVotes() {
		var votesList = []

		state.voteOptions.forEach(function(option) {
			votesList.push(
				{
					option: option.id,
					votes: state.votes.filter(obj => {
						return obj.voteOptionText === option.text
					})
				}
			)
		})
		return votesList
	},

	countParticipants(state) {
		return state.participants.length
	},

	countComments(state) {
		return state.comments.length
	},

	adminMode(state) {
		return (state.event.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
	},

	accessType(state) {
		if (state.event.access === 'public') {
			return t('polls', 'Public access')
		} else if (state.event.access === 'select') {
			return t('polls', 'Only shared')
		} else if (state.event.access === 'registered') {
			return t('polls', 'Registered users only')
		} else if (state.event.access === 'hidden') {
			return t('polls', 'Hidden poll')
		} else {
			return ''
		}
	},

	participantsVotes(state) {
		var votesList = []
		var templist = []
		var foundVote = []
		var fakeVoteId = 78946456

		state.participants.forEach(function(participant) {
			templist = []
			state.voteOptions.forEach(function(voteOption) {
				foundVote = state.votes.filter(obj => {
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

			votesList.push({
				name: participant,
				votes: templist
			})

		})

		return votesList
	},

	myVotes(state, getters) {
		return getters.participantsVotes.filter(vote => vote.name === state.currentUser)
	},

	otherVotes(state, getters) {
		return getters.participantsVotes.filter(vote => vote.name !== state.currentUser)
	},

	otherParticipants(state) {
		return state.participants.filter(participant => participant !== state.currentUser)
	}

}

const actions = {
	addShare({ commit }, payload) {
	// 	this.poll.shares.push(item)
	},

	updateShares({ commit }, payload) {
	// 	this.poll.shares = share.slice(0)
	},

	removeShare({ commit }, payload) {
	// 	this.shares.splice(this.shares.indexOf(item), 1)
	},

	loadPoll({ commit }, payload) {
		commit({ type: 'resetPoll' })
		if (payload.mode !== 'create') {

			axios.get(OC.generateUrl('apps/polls/get/poll/' + payload.hash))
				.then((response) => {
					commit('setPoll', { 'poll': response.data })
					commit('setPollProperty', {'property': 'currentUser', 'value': OC.getCurrentUser().uid})
					switch (payload.mode) {
					case 'edit':
						commit('setPollProperty', { 'property': 'mode', 'value': payload.mode })
						break
					case 'vote':
						commit('setPollProperty', { 'property': 'mode', 'value': payload.mode })
						break
					case 'clone':
						commit('setPollProperty', { 'property': 'mode', 'value': 'create' })
						commit('setPollProperty', { 'property': 'comments', 'value': [] })
						commit('setPollProperty', { 'property': 'shares', 'value': [] })
						commit('setPollProperty', { 'property': 'participants', 'value': [] })
						commit('setPollProperty', { 'property': 'votes', 'value': [] })
						commit('setEventProperty', { 'property': 'owner', 'value': OC.getCurrentUser().uid })
						break
					}

				}, (error) => {
				/* eslint-disable-next-line no-console */
					console.log(error)
				})
		}
	},

	writeVotePromise({ commit }) {
	},

	writePollPromise({ commit }) {
		console.log('writePollPromise ', state.mode, state.event.id)
		if (state.mode !== 'vote') {
			console.log('state.mode != vote')

			return axios.post(OC.generateUrl('apps/polls/write/poll'), { event: state.event, voteOptions: state.voteOptions, shares: state.shares, mode: state.mode })
				.then((response) => {
					commit('setPollProperty', { 'property': 'mode', 'value': 'edit' })
					commit('setPollProperty', { 'property': 'id', 'value': response.data.id })
					commit('setEventProperty', { 'property': 'id', 'value': response.data.id })
					commit('setEventProperty', { 'property': 'hash', 'value': response.data.hash })
				// window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash)
				}, (error) => {
					state.poll.event.hash = ''
					/* eslint-disable-next-line no-console */
					console.log(error.response)
				})

		}
	}
}

export default { state, mutations, getters, actions }
