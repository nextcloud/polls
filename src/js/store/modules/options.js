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

	optionAdd(state, payload) {
		state.list.push(payload)
	},

	datesShift(state, payload) {
		state.list.forEach(function(option) {
			option.text = moment(option.text).add(payload.step, payload.unit).format('YYYY-MM-DD HH:mm:ss')
			option.timestamp = moment.utc(option.text).unix()
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

	loadOptions({ commit, rootState }, payload) {
		return axios.get(OC.generateUrl('apps/polls/get/options/' + payload.pollId))
			.then((response) => {
				commit('optionsSet', { 'list': response.data })
			}, (error) => {
				commit({ type: 'optionsReset' })
				/* eslint-disable-next-line no-console */
				console.error(error)
			})
	},

	addOption({ commit, getters, dispatch, rootState }, payload) {
		var newOption = {}

		newOption.id = getters.lastOptionId + 1
		newOption.pollId = rootState.event.id

		if (rootState.event.type === 'datePoll') {
			newOption.timestamp = moment(payload.option).unix()
			newOption.text = moment.utc(payload.option).format('YYYY-MM-DD HH:mm:ss')

		} else if (rootState.event.type === 'textPoll') {
			newOption.timestamp = 0
			newOption.text = payload.option
		}

		if (state.currentUser !== '') {

			return axios.post(OC.generateUrl('apps/polls/add/option'), { pollId: rootState.event.id, option: newOption })
				.then((response) => {
					commit('optionsSet', { 'list': response.data })
				}, (error) => {
				/* eslint-disable-next-line no-console */
					console.error(error.response)
				})
		}
	},

	removeOption({ commit, getters, dispatch, rootState }, optionId) {
		if (state.currentUser !== '') {
			return axios.post(OC.generateUrl('apps/polls/add/option'), { optionId: optionId })
				.then((response) => {
					commit('optionsSet', { 'list': response.data })
				}, (error) => {
				/* eslint-disable-next-line no-console */
					console.error(error.response)
				})
		}

		commit('addOption', newOption)
		dispatch('writeOptionsPromise')

	},

	writeOptionsPromise({ commit, getters, rootState }, payload) {
		if (state.currentUser !== '') {
			return axios.post(OC.generateUrl('apps/polls/write/options'), { pollId: rootState.event.id, options: state.list })
				.then((response) => {
					commit('optionsSet', { 'list': response.data })
				}, (error) => {
				/* eslint-disable-next-line no-console */
					console.error(error.response)
				})
		}
	}
}

export default { state, mutations, getters, actions }
