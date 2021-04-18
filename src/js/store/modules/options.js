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
import { generateUrl } from '@nextcloud/router'
import orderBy from 'lodash/orderBy'

const defaultOptions = () => ({
	list: [],
	ranked: false,
})

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

	setRankOrder(state, payload) {
		if (payload) {
			state.ranked = payload
		} else {
			state.ranked = !state.ranked
		}
	},

	delete(state, payload) {
		state.list = state.list.filter((option) => option.id !== payload.option.id)
	},

	confirm(state, payload) {
		const index = state.list.findIndex((option) => option.id === payload.option.id)

		state.list[index].confirmed = !state.list[index].confirmed
	},

	setItem(state, payload) {
		const index = state.list.findIndex((option) => option.id === payload.option.id)

		if (index < 0) {
			state.list.push(payload.option)
		} else {
			state.list.splice(index, 1, payload.option)
		}
	},
}

const getters = {
	count: (state) => state.list.length,

	rankedOptions: (state) => orderBy(state.list, state.ranked ? 'rank' : 'order', 'asc'),

	proposalsExist: (state) => !!state.list.filter((option) => option.owner).length,

	confirmed: (state) => state.list.filter((option) => option.confirmed > 0),
}

const actions = {

	async list(context) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else if (context.rootState.route.name === 'vote') {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		} else if (context.rootState.route.name === 'list' && context.rootState.route.params.id) {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		} else {
			context.commit('reset')
			return
		}

		try {
			const response = await axios.get(generateUrl(endPoint + '/options'))
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error('Error loding options', { error: e.response }, { pollId: context.rootState.route.params.id })
			throw e
		}
	},

	async add(context, payload) {
		const endPoint = 'apps/polls/option'
		try {
			const response = await axios.post(generateUrl(endPoint), {
				pollId: context.rootState.route.params.id,
				timestamp: payload.timestamp,
				pollOptionText: payload.pollOptionText,
				duration: payload.duration,
			})
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			console.error('Error adding option: ' + e.response.data, { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async update(context, payload) {
		const endPoint = 'apps/polls/option'
		try {
			const response = await axios.put(generateUrl(endPoint + '/' + payload.option.id), {
				timestamp: payload.option.timestamp,
				pollOptionText: payload.option.timeStamp,
				duration: payload.option.duration,
			})
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			console.error('Error updating option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async delete(context, payload) {
		const endPoint = 'apps/polls/option'
		try {
			await axios.delete(generateUrl(endPoint + '/' + payload.option.id))
			context.commit('delete', { option: payload.option })
		} catch (e) {
			console.error('Error deleting option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async confirm(context, payload) {
		context.commit('confirm', { option: payload.option })
		const endPoint = 'apps/polls/option'
		try {
			const response = await axios.put(generateUrl(endPoint + '/' + payload.option.id + '/confirm'))
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			console.error('Error confirming option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async reorder(context, payload) {
		context.commit('reorder', { options: payload })
		const endPoint = 'apps/polls/poll/' + context.rootState.route.params.id + '/options/reorder'
		try {
			const response = await axios.post(generateUrl(endPoint), {
				options: payload,
			})
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error('Error reordering option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async sequence(context, payload) {
		const endPoint = 'apps/polls/option'
		try {
			const response = await axios.post(generateUrl(endPoint + '/' + payload.option.id + '/sequence'), {
				step: payload.sequence.step,
				unit: payload.sequence.unit.value,
				amount: payload.sequence.amount,
			})
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error('Error creating sequence', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async shift(context, payload) {
		const endPoint = 'apps/polls/poll/' + context.rootState.route.params.id + '/shift'
		try {
			const response = await axios.post(generateUrl(endPoint), {
				step: payload.shift.step,
				unit: payload.shift.unit.value,
			})
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error('Error shifting dates', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async getEvents(context, payload) {
		const endPoint = 'apps/polls/option'
		try {
			return await axios.get(generateUrl(endPoint + '/' + payload.option.id + '/events'))
		} catch (e) {
			return { events: [] }
		}
	},

}

export default { state, mutations, getters, actions, namespaced }
