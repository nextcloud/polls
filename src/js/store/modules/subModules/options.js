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
import moment from '@nextcloud/moment'
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

	delete(state, payload) {
		state.list = state.list.filter(option => {
			return option.id !== payload.option.id
		})
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
	reorder(context, payload) {
		const endPoint = 'apps/polls/option/reorder'
		return axios.post(generateUrl(endPoint), { pollId: context.rootState.poll.id, options: payload })
			.then((response) => {
				context.commit('set', { options: response.data })
			})
			.catch((error) => {
				console.error('Error reordering option', { error: error.response }, { payload: payload })
				throw error
			})
	},

	update(context, payload) {
		const endPoint = 'apps/polls/option/update'

		return axios.post(generateUrl(endPoint), { option: payload.option })
			.then((response) => {
				context.commit('setItem', { option: response.data })
			})
			.catch((error) => {
				console.error('Error updating option', { error: error.response }, { payload: payload })
				throw error
			})
	},

	add(context, payload) {
		const endPoint = 'apps/polls/option/add/'
		const option = {}

		option.id = 0
		option.pollId = context.rootState.poll.id

		if (context.rootState.poll.type === 'datePoll') {
			if (payload.timestamp) {
				option.timestamp = payload.timestamp
			} else {
				option.timestamp = moment(payload.pollOptionText).unix()
			}
			option.order = option.timestamp
			option.pollOptionText = moment.utc(payload.pollOptionText).format('YYYY-MM-DD HH:mm:ss')

		} else if (context.rootState.poll.type === 'textPoll') {
			option.timestamp = 0
			option.order = state.list.length + 1
			option.pollOptionText = payload.pollOptionText
		}

		return axios.post(generateUrl(endPoint), { option: option })
			.then((response) => {
				context.commit('setItem', { option: response.data })
			})
			.catch((error) => {
				console.error('Error adding option', { error: error.response }, { payload: payload })
				throw error
			})
	},

	delete(context, payload) {
		const endPoint = 'apps/polls/option/remove/'

		return axios.post(generateUrl(endPoint), { option: payload.option })
			.then(() => {
				context.commit('delete', { option: payload.option })
			})
			.catch((error) => {
				console.error('Error removing option', { error: error.response }, { payload: payload })
				throw error
			})
	},
}

export default { state, mutations, getters, actions, namespaced }
