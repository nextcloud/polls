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

const defaultOptions = () => {
	return {
		list: [],
	}
}

const state = defaultOptions()

const namespaced = true

const mutations = {
	set(state, payload) {
		state.list = payload.options
	},

	reset(state) {
		Object.assign(state, defaultOptions())
	},

	reorder(state, payload) {
		payload.options.forEach((item, i) => {
			item.order = i + 1
		})
		state.list = payload.options
	},

	delete(state, payload) {
		state.list = state.list.filter(option => {
			return option.id !== payload.option.id
		})
	},

	confirm(state, payload) {
		const index = state.list.findIndex((option) => {
			return option.id === payload.option.id
		})

		state.list[index].confirmed = !state.list[index].confirmed
	},

	setItem(state, payload) {
		const index = state.list.findIndex((option) => {
			return option.id === payload.option.id
		})

		if (index < 0) {
			state.list.push(payload.option)
		} else {
			state.list.splice(index, 1, payload.option)
		}
	},
}

const getters = {
	sorted: (state, getters, rootState, rootGetters) => {
		let rankedOptions = []
		state.list.forEach((option) => {
			rankedOptions.push({
				...option,
				rank: 0,
				no: 0,
				yes: rootState.poll.votes.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'yes').length,
				maybe: rootState.poll.votes.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'maybe').length,
				realno: rootState.poll.votes.list.filter(vote => vote.voteOptionText === option.pollOptionText && vote.voteAnswer === 'no').length,
				votes: rootGetters['poll/participantsVoted'].length,
			})
		})

		rankedOptions = orderBy(rankedOptions, ['yes', 'maybe'], ['desc', 'desc'])

		for (let i = 0; i < rankedOptions.length; i++) {
			rankedOptions[i].no = rankedOptions[i].votes - rankedOptions[i].yes - rankedOptions[i].maybe
			if (i > 0 && rankedOptions[i].yes === rankedOptions[i - 1].yes && rankedOptions[i].maybe === rankedOptions[i - 1].maybe) {
				rankedOptions[i].rank = rankedOptions[i - 1].rank
			} else {
				rankedOptions[i].rank = i + 1
			}
		}

		return orderBy(rankedOptions, 'order')
	},

	confirmed: state => {
		return state.list.filter(option => {
			return option.confirmed > 0
		})
	},
}

const actions = {

	reload(context) {
		const endPoint = 'apps/polls/polls'
		return axios.get(generateUrl(endPoint + '/' + context.rootState.poll.id + '/options'))
			.then((response) => {
				context.commit('set', { options: response.data.options })
			})
			.catch((error) => {
				console.error('Error loding options', { error: error.response }, { pollId: context.rootState.poll.id })
				throw error
			})
	},

	add(context, payload) {
		const endPoint = 'apps/polls/option'
		return axios.post(generateUrl(endPoint), {
			pollId: context.rootState.poll.id,
			timestamp: payload.timestamp,
			pollOptionText: payload.pollOptionText,
		})
			.then((response) => {
				context.commit('setItem', { option: response.data.option })
			})
			.catch((error) => {
				console.error('Error adding option: ' + error.response.data, { error: error.response }, { payload: payload })
				context.dispatch('reload')
				throw error
			})
	},

	update(context, payload) {
		const endPoint = 'apps/polls/option'
		return axios.put(generateUrl(endPoint + '/' + payload.option.id), {
			timestamp: payload.option.timestamp,
			pollOptionText: payload.option.timeStamp,
		})
			.then((response) => {
				context.commit('setItem', { option: response.data.option })
			})
			.catch((error) => {
				console.error('Error updating option', { error: error.response }, { payload: payload })
				context.dispatch('reload')
				throw error
			})
	},

	delete(context, payload) {
		const endPoint = 'apps/polls/option'

		return axios.delete(generateUrl(endPoint + '/' + payload.option.id))
			.then((response) => {
				context.commit('delete', { option: payload.option })
			})
			.catch((error) => {
				console.error('Error deleting option', { error: error.response }, { payload: payload })
				context.dispatch('reload')
				throw error
			})
	},

	confirm(context, payload) {
		context.commit('confirm', { option: payload.option })

		const endPoint = 'apps/polls/option'
		return axios.put(generateUrl(endPoint + '/' + payload.option.id, '/confirm'))
			.then((response) => {
				context.commit('setItem', { option: response.data.option })
			})
			.catch((error) => {
				console.error('Error confirming option', { error: error.response }, { payload: payload })
				context.dispatch('reload')
				throw error
			})
	},

	reorder(context, payload) {
		const endPoint = 'apps/polls/polls'
		context.commit('reorder', { options: payload })
		return axios.post(generateUrl(endPoint + '/' + context.rootState.poll.id + '/options/reorder'), {
			options: payload,
		})
			.then((response) => {
				context.commit('set', { options: response.data.options })
			})
			.catch((error) => {
				console.error('Error reordering option', { error: error.response }, { payload: payload })
				context.dispatch('reload')
				throw error
			})
	},

	sequence(context, payload) {
		const endPoint = 'apps/polls/option'
		return axios.post(generateUrl(endPoint + '/' + payload.option.id + '/sequence'), {
			step: payload.sequence.step,
			unit: payload.sequence.unit.value,
			amount: payload.sequence.amount,
		})
			.then((response) => {
				context.commit('set', { options: response.data.options })
			})
			.catch((error) => {
				console.error('Error creating sequence', { error: error.response }, { payload: payload })
				context.dispatch('reload')
				throw error
			})
	},

	getEvents(context, payload) {
		const endPoint = 'apps/polls/option'
		return axios.get(generateUrl(endPoint + '/' + payload.option.id + '/events'))
			.then((response) => {
				return response.data
			})
			.catch((error) => {
				if (error.message === 'Network Error') {
					console.error('Got an ugly network error while loading calendar events', { error: error }, { payload: payload })
					throw error
				}
				console.error('Error loading calendar events - start whistling and behave as if nothing happened', { error: error }, { payload: payload })
				return { events: [] }
			})
	},

}

export default { state, mutations, getters, actions, namespaced }
