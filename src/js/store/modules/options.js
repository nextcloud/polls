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
import orderBy from 'lodash/orderBy'
import moment from '@nextcloud/moment'

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
	rankedOptions: (state) => orderBy(state.list, state.ranked ? 'computed.rank' : 'order', 'asc'),
	proposalsExist: (state) => !!state.list.filter((option) => option.owner.userId).length,
	confirmed: (state) => state.list.filter((option) => option.confirmed > 0),

	explodeDates: (state, getters, rootState) => (option) => {
		const from = moment.unix(option.timestamp)
		const to = moment.unix(option.timestamp + Math.max(0, option.duration))
		// does the event start at 00:00 local time and
		// is the duration divisable through 24 hours without rest
		// then we have a day long event (one or multiple days)
		// In this case we want to suppress the display of any time information
		const dayLongEvent = from.unix() === moment(from).startOf('day').unix() && to.unix() === moment(to).startOf('day').unix() && from.unix() !== to.unix()

		const dayModifier = dayLongEvent ? 1 : 0
		// modified to date, in case of day long events, a second gets substracted
		// to set the begin of the to day to the end of the previous date
		const toModified = moment(to).subtract(dayModifier, 'days')

		if (rootState.poll.type !== 'datePoll') {
			return {}
		}
		return {
			from: {
				month: from.format(moment().year() === from.year() ? 'MMM' : 'MMM [ \']YY'),
				day: from.format('D'),
				dow: from.format('ddd'),
				time: from.format('LT'),
				date: from.format('ll'),
				dateTime: from.format('llll'),
				iso: moment(from).toISOString(),
				utc: moment(from).utc().format('llll'),
			},
			to: {
				month: toModified.format(moment().year() === toModified.year() ? 'MMM' : 'MMM [ \']YY'),
				day: toModified.format('D'),
				dow: toModified.format('ddd'),
				time: to.format('LT'),
				date: toModified.format('ll'),
				dateTime: to.format('llll'),
				iso: moment(to).toISOString(),
				utc: moment(to).utc().format('llll'),
				sameDay: from.format('L') === toModified.format('L'),
			},
			dayLong: dayLongEvent,
			raw: `${from.format('llll')} - ${toModified.format('llll')}`,
			iso: `${moment(from).toISOString()} - ${moment(to).toISOString()}`,
		}

	},

}

const actions = {

	async list(context) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = `${endPoint}/s/${context.rootState.route.params.token}/options`
		} else if (context.rootState.route.name === 'vote') {
			endPoint = `${endPoint}/poll/${context.rootState.route.params.id}/options`
		} else if (context.rootState.route.name === 'list' && context.rootState.route.params.id) {
			endPoint = `${endPoint}/poll/${context.rootState.route.params.id}/options`
		} else {
			context.commit('reset')
			return
		}

		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error('Error loding options', { error: e.response }, { pollId: context.rootState.route.params.id })
			throw e
		}
	},

	async add(context, payload) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = `${endPoint}/s/${context.rootState.route.params.token}/option`
		} else {
			endPoint = `${endPoint}/option`
		}

		try {
			const response = await axios.post(generateUrl(endPoint), {
				pollId: context.rootState.route.params.id,
				timestamp: payload.timestamp,
				text: payload.text,
				duration: payload.duration,
			})
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			console.error(`Error adding option: ${e.response.data}`, { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async addBulk(context, payload) {
		const endPoint = 'apps/polls/option/bulk'

		try {
			const response = await axios.post(generateUrl(endPoint), {
				pollId: context.rootState.route.params.id,
				text: payload.text,
			})
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error(`Error adding option: ${e.response.data}`, { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async update(context, payload) {
		const endPoint = `apps/polls/option/${payload.option.id}`

		try {
			const response = await axios.put(generateUrl(endPoint), {
				timestamp: payload.option.timestamp,
				text: payload.option.timeStamp,
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
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = `${endPoint}/s/${context.rootState.route.params.token}/option/${payload.option.id}`
		} else {
			endPoint = `${endPoint}/option/${payload.option.id}`
		}

		try {
			await axios.delete(generateUrl(endPoint))
			context.commit('delete', { option: payload.option })
		} catch (e) {
			console.error('Error deleting option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async confirm(context, payload) {
		const endPoint = `apps/polls/option/${payload.option.id}/confirm`

		context.commit('confirm', { option: payload.option })

		try {
			const response = await axios.put(generateUrl(endPoint))
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			console.error('Error confirming option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async reorder(context, payload) {
		const endPoint = `apps/polls/poll/${context.rootState.route.params.id}/options/reorder`

		context.commit('reorder', { options: payload })

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
		const endPoint = `apps/polls/option/${payload.option.id}/sequence`

		try {
			const response = await axios.post(generateUrl(endPoint), {
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
		const endPoint = `apps/polls/poll/${context.rootState.route.params.id}/shift`

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
		const endPoint = `apps/polls/option/${payload.option.id}/events`

		try {
			return await axios.get(generateUrl(endPoint), {
				params: {
					tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
				},
			})
		} catch (e) {
			return { events: [] }
		}
	},

}

export default { state, mutations, getters, actions, namespaced }
