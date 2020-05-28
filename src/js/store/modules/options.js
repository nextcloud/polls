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
import sortBy from 'lodash/sortBy'
import moment from '@nextcloud/moment'
import { generateUrl } from '@nextcloud/router'

const defaultOptions = () => {
	return {
		options: [],
	}
}

const state = defaultOptions()

const mutations = {
	set(state, payload) {
		state.options = payload.options
	},

	reset(state) {
		Object.assign(state, defaultOptions())
	},

	removeOption(state, payload) {
		state.options = state.options.filter(option => {
			return option.id !== payload.option.id
		})
	},

	reorderOptions(state, payload) {
		payload.forEach((item, i) => {
			item.order = i + 1
		})
	},

	setOption(state, payload) {
		const index = state.options.findIndex((option) => {
			return option.id === payload.option.id
		})

		if (index < 0) {
			state.options.push(payload.option)
		} else {
			state.options.splice(index, 1, payload.option)
		}
	},
}

const getters = {
	lastOptionId: state => {
		return Math.max.apply(Math, state.options.map(function(option) {
			return option.id
		}))
	},

	sortedOptions: state => {
		return sortBy(state.options, 'order')
	},
}

const actions = {
	updateOptions(context) {
		context.state.options.forEach((item, i) => {
			context.dispatch('updateOptionAsync', { option: item })
		})
	},

	updateOptionAsync(context, payload) {
		const endPoint = 'apps/polls/option/update'

		return axios.post(generateUrl(endPoint), { option: payload.option })
			.then(() => {
				context.commit('setOption', { option: payload.option })
			}, (error) => {
				console.error('Error updating option', { error: error.response }, { payload: payload })
				throw error
			})
	},

	addOptionAsync(context, payload) {
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
			option.order = state.options.length + 1
			option.pollOptionText = payload.pollOptionText
		}

		return axios.post(generateUrl(endPoint), { option: option })
			.then((response) => {
				context.commit('setOption', { option: response.data })
			}, (error) => {
				console.error('Error adding option', { error: error.response }, { payload: payload })
				throw error
			})
	},

	removeOptionAsync(context, payload) {
		const endPoint = 'apps/polls/option/remove/'

		return axios.post(generateUrl(endPoint), { option: payload.option })
			.then(() => {
				context.commit('removeOption', { option: payload.option })
			}, (error) => {
				console.error('Error removing option', { error: error.response }, { payload: payload })
				throw error
			})
	},
}

export default { state, mutations, getters, actions }
