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

const defaultOptions = () => {
	return {
		list: []
	}
}

const state = defaultOptions()

const mutations = {
	optionsSet(state, payload) {
		Object.assign(state, payload)
	},

	reset(state) {
		Object.assign(state, defaultOptions())
	},

	optionRemove(state, payload) {
		state.list = state.list.filter(option => {
			return option.id !== payload.option.id
		})
	},

	reorderOptions(state, payload) {
		payload.forEach((item, i) => {
			item.order = i + 1
		})
	},

	setOption(state, payload) {
		const index = state.list.findIndex((option) => {
			return option.id === payload.option.id
		})

		if (index < 0) {
			state.list.push(payload.option)
		} else {
			state.list.splice(index, 1, payload.option)
		}
	}
}

const getters = {
	lastOptionId: state => {
		return Math.max.apply(Math, state.list.map(function(option) {
			return option.id
		}))
	},

	sortedOptions: state => {
		return sortBy(state.list, 'order')
	}
}

const actions = {

	loadPoll(context, payload) {
		let endPoint = 'apps/polls/options/get/'

		if (payload.token !== undefined) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			context.commit('reset')
			return
		}

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				context.commit('optionsSet', { list: response.data })
			}, (error) => {
				context.commit('reset')
				console.error('Error loading options', { error: error.response }, { payload: payload })
				throw error
			})
	},

	updateOptions(context) {
		context.state.list.forEach((item, i) => {
			context.dispatch('updateOptionAsync', { option: item })
		})
	},

	updateOptionAsync(context, payload) {
		const endPoint = 'apps/polls/option/update'

		return axios.post(OC.generateUrl(endPoint), { option: payload.option })
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
			option.order = state.list.length + 1
			option.pollOptionText = payload.pollOptionText
		}

		return axios.post(OC.generateUrl(endPoint), { option: option })
			.then((response) => {
				context.commit('setOption', { option: response.data })
			}, (error) => {
				console.error('Error adding option', { error: error.response }, { payload: payload })
				throw error
			})
	},

	removeOptionAsync(context, payload) {
		const endPoint = 'apps/polls/option/remove/'

		return axios.post(OC.generateUrl(endPoint), { option: payload.option })
			.then(() => {
				context.commit('optionRemove', { option: payload.option })
			}, (error) => {
				console.error('Error removing option', { error: error.response }, { payload: payload })
				throw error
			})
	}
}

export default { state, mutations, getters, actions }
