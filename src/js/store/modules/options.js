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

const defaultOptions = () => {
	return {
		list: [],
		pollId: 0
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

	dateAdd(state, payload) {
		state.list.push({
			id: 0,
			timestamp: moment(payload).unix(),
			text: moment.utc(payload).format('YYYY-MM-DD HH:mm:ss')
		})
	},

	textAdd(state, payload) {
		state.list.push({
			id: 0,
			timestamp: 0,
			text: payload
		})
	},

	datesShift(state, payload) {
		state.list.forEach(function(option) {
			option.text = moment(option.text).add(payload.step, payload.unit).format('YYYY-MM-DD HH:mm:ss')
			option.timestamp = moment.utc(option.text).unix()
		})
	},

	optionRemove(state, payload) {
		state.list.splice(state.list.findIndex(function(voteOption) {
			return voteOption === payload}), 1)
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

	loadOptions({ commit }, payload) {
		commit({ type: 'optionsReset' })
			return axios.get(OC.generateUrl('apps/polls/get/options/' + payload.pollId))
			.then((response) => {
				commit('optionsSet', { 'list': response.data, 'pollId': payload.pollId })
			}, (error) => {
				commit('optionsSet', { 'list': [], 'pollId': 0 })
			/* eslint-disable-next-line no-console */
				console.log(error)
			})
	},

	writeOptionsPromise({ commit }, payload) {
		return
		if (state.currentUser !== '') {
			return axios.post(OC.generateUrl('apps/polls/write/options'), { pollId: payload.pollId, options: state.list })
				.then((response) => {
					commit('optionsSet', { 'list': response.data, 'pollId': payload.pollId })
				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
				})
		}
	}
}

export default { state, mutations, getters, actions }
