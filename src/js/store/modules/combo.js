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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { uniqueOptions, uniqueParticipants } from '../../helpers/arrayHelper.js'
import sortBy from 'lodash/sortBy'

const defaultCombo = () => ({
	id: 1,
	title: 'Combined polls',
	description: 'Combine multiple date polls in a single view',
	options: [],
	polls: [],
	participants: [],
	votes: [],
})

const state = defaultCombo()

const namespaced = true

const mutations = {
	set(state, payload) {
		Object.assign(state, payload.poll)
	},

	reset(state) {
		Object.assign(state, defaultCombo())
	},

	addPoll(state, payload) {
		state.polls.push(payload.poll)
	},

	addOptions(state, payload) {
		state.options.push(...payload.options)
	},

	addVotes(state, payload) {
		state.votes.push(...payload.votes)
		state.participants = uniqueParticipants(state.votes)
	},

	removePoll(state, payload) {
		state.polls = state.polls.filter((poll) => poll.id !== payload.pollId)
	},

	removeVotes(state, payload) {
		state.votes = state.votes.filter((vote) => vote.pollId !== payload.pollId)
		state.participants = uniqueParticipants(state.votes)
	},

	removeOptions(state, payload) {
		state.options = state.options.filter((option) => option.pollId !== payload.pollId)
	},

}

const getters = {
	poll: (state) => (pollId) => state.polls.find((poll) => poll.id === pollId),
	votesInPoll: (state) => (pollId) => state.votes.filter((vote) => vote.pollId === pollId),
	participantsInPoll: (state) => (pollId) => state.participants.filter((participant) => participant.pollId === pollId),
	pollIsListed: (state) => (pollId) => !!state.polls.find((poll) => poll.id === pollId),
	optionBelongsToPoll: (state) => (payload) => !!state.options.find((option) => option.pollOptionText === payload.pollOptionText && option.pollId === payload.pollId),
	uniqueOptions: (state) => sortBy(uniqueOptions(state.options), 'timestamp'),

	getVote: (state) => (payload) => {
		const found = state.votes.find((vote) => (
			vote.userId === payload.user.userId
            && vote.voteOptionText === payload.option.pollOptionText
            && vote.pollId === payload.user.pollId))
		if (found === undefined) {
			return {
				voteAnswer: '',
				voteOptionText: payload.option.pollOptionText,
				userId: payload.userId,
			}
		}
		return found
	},
}

const actions = {

	reset(context) {
		// context.commit('reset')
	},

	async add(context, pollId) {
		context.dispatch('addPoll', { pollId })
		context.dispatch('addVotes', { pollId })
		context.dispatch('addOptions', { pollId })
	},

	async remove(context, pollId) {
		context.commit('removePoll', { pollId })
		context.commit('removeVotes', { pollId })
		context.commit('removeOptions', { pollId })
	},

	async cleanUp(context) {
		context.state.polls.forEach((comboPoll) => {
			if (context.rootState.polls.list.findIndex((poll) => poll.id === comboPoll.id && !poll.deleted) < 0) {
				context.commit('removePoll', { pollId: comboPoll.id })
			}
		})
	},

	async togglePollItem(context, pollId) {
		if (context.getters.pollIsListed(pollId)) {
			context.dispatch('remove', pollId)
		} else {
			context.dispatch('add', pollId)
		}
	},

	async addPoll(context, payload) {
		const endPoint = `apps/polls/poll/${payload.pollId}/poll`
		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('addPoll', response.data)
		} catch (e) {
			console.debug('Error loading poll for combo', { error: e.response })
		}
	},

	async addOptions(context, payload) {
		const endPoint = `apps/polls/poll/${payload.pollId}/options`

		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('addOptions', response.data)
		} catch (e) {
			console.debug('Error loading options for combo', { error: e.response })
		}
	},

	async addVotes(context, payload) {
		const endPoint = `apps/polls/poll/${payload.pollId}/votes`

		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('addVotes', response.data)
		} catch (e) {
			console.debug('Error loading options for combo', { error: e.response })
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
