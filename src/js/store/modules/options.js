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
import sortBy from 'lodash/sortBy'
import moment from 'moment'

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

	optionsReset(state) {
		Object.assign(state, defaultOptions())
	},

	optionRemove(state, payload) {
		state.list = state.list.filter((option) => {
			return option.id !== payload.option.id
		})
	},

	optionAdd(state, payload) {
		state.list.push(payload.option)
	},

	datesShift(state, payload) {
		state.list.forEach(function(option) {
			option.pollOptionText = moment(option.pollOptionText).add(payload.step, payload.unit).format('YYYY-MM-DD HH:mm:ss')
			option.timestamp = moment.utc(option.pollOptionText).unix()
		})
	}

}

const getters = {
	lastOptionId: state => {
		return Math.max.apply(Math, state.list.map(function(o) { return o.id }))
	},

	sortedOptions: state => {
		return sortBy(state.list, 'timestamp')
	}

}

const actions = {

	loadPoll({ commit, rootState }, payload) {
		return axios.get(OC.generateUrl('apps/polls/get/options/' + payload.pollId))
			.then((response) => {
				commit('optionsSet', { 'list': response.data })
			}, (error) => {
				commit({ type: 'optionsReset' })
				console.error(error)
			})
	},

	addOption({ commit, getters, dispatch, rootState }, payload) {
		var option = {}

		option.id = getters.lastOptionId + 1
		option.pollId = rootState.event.id

		if (rootState.event.type === 'datePoll') {
			option.timestamp = moment(payload.option).unix()
			option.pollOptionText = moment.utc(payload.option).format('YYYY-MM-DD HH:mm:ss')

		} else if (rootState.event.type === 'textPoll') {
			option.timestamp = 0
			option.pollOptionText = payload.option
		}

		console.log('before', option)

		return axios.post(OC.generateUrl('apps/polls/add/option'), { pollId: rootState.event.id, option: option })
			.then((response) => {
				console.log('after', option)
				console.log('response', response.data)
				commit('optionAdd', { 'option': response.data })
				// commit('optionsSet', { 'list': response.data })
			}, (error) => {
				console.error(error.response)
			})
	},

	removeOption({ commit, getters, dispatch, rootState }, option) {
		return axios.post(OC.generateUrl('apps/polls/remove/option'), { option: option })
			.then((response) => {
				commit('optionRemove', { 'option': option })
			}, (error) => {
				console.error(error.response)
			})
	},

	writeOptionsPromise({ commit, getters, rootState }, payload) {
		return axios.post(OC.generateUrl('apps/polls/write/options'), { pollId: rootState.event.id, options: state.list })
			.then((response) => {
				commit('optionsSet', { 'list': response.data })
			}, (error) => {
				console.error(error.response)
			})
	}
}

export default { state, mutations, getters, actions }
