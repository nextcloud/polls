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

	addParticipant(state, payload) {
			var fakeVoteId=6541315463
			state.participants.push(payload.userId)
			state.voteOptions.forEach(function(option) {
				state.votes.push({
					id: ++fakeVoteId,
					pollId: state.event.id,
					userId: payload.userId,
					voteAnswer: 'unvoted',
					voteOptionText: option.text,
					voteOptionId: option.id
				}
			)
		})
	},

	addText(state, payload) {
		state.voteOptions.push({
			id: 0,
			timestamp: 0,
			text: payload
		})
	},

	addComment(state, payload) {
		state.comments.push({
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

	// TODO: Find a better solution than this
	changeVote(state, payload) {
		state.votes.forEach(function(vote){
			if (vote.id === payload.payload.id && vote.userId === payload.payload.userId && vote.voteOptionText === payload.payload.voteOptionText) {
				vote.voteAnswer = payload.switchTo
				vote.voteOptionId = payload.payload.id
			}
		})
	},

	removeDate(state, payload) {
	}

}

const getters = {
	lastVoteId(state) {
		return Math.max.apply(Math, state.votes.map(function(o) { return o.id }))
	},

	currentUserParticipated(state) {
		return (state.votes.filter(function(vote) {
			return vote.userId === state.currentUser
		}).length > 0)
	},

	timeSpanCreated(state) {
		return moment(state.event.created).fromNow()
	},

	sortedComments(state) {
		return sortBy(state.comments, 'date').reverse()
	},

	timeSpanExpiration(state) {
		if (state.event.expiration) {
			return moment(state.event.expirationDate).fromNow()
		} else {
			return t('polls', 'never')
		}
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

	allVotes(state) {
		var votesList = []
		var foundVote = []
		var fakeVoteId = 78946456

		state.participants.forEach(function(participant) {
			state.voteOptions.forEach(function(voteOption) {
				foundVote = state.votes.filter(vote => {
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
		if (state.event.type === 'datePoll') {
			return sortBy(votesList, 'voteOptionText')
		} else {
			return votesList
		}
	},

	usersVotes: (state, getters) => (userId) => {
		return getters.allVotes.filter(vote => {
			return vote.userId === userId
		})
	},

	sortedVoteOptions(state) {
		if (state.event.type === 'datePoll') {
			return sortBy(state.voteOptions, 'timestamp')
		} else {
			return state.voteOptions
		}
	},

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

	addMe({commit}) {
		if (!getters.currentUserParticipated && !state.event.expired) {
			commit('addParticipant', {'userId': state.currentUser})
		}
	},

	loadComments({ commit }) {
		axios.get(OC.generateUrl('apps/polls/get/comments/' + state.id))
		.then((response) => {
			commit('setPollProperty', {'property': 'comments', 'value': response.data})
		}, (error) => {
		/* eslint-disable-next-line no-console */
			console.log(error)
		})
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

	writeCommentPromise({ commit }, payload) {
		if (state.currentUser !== '') {
			return axios.post(OC.generateUrl('apps/polls/write/comment'), { pollId: state.event.id, currentUser: state.currentUser, commentContent: payload })
				.then((response) => {
				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
				})
		}
	},

	writeVotePromise({ commit }) {
		if (state.mode === 'vote' && state.currentUser !== '') {
			var usersVotes = state.votes.filter(vote => {
				return vote.userId === state.currentUser
			})

			return axios.post(OC.generateUrl('apps/polls/write/vote'), { pollId: state.event.id, votes: usersVotes, mode: state.mode, currentUser: state.currentUser })
				.then((response) => {

				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
				})
		}
	},

	writePollPromise({ commit }) {
		if (state.mode !== 'vote') {

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
