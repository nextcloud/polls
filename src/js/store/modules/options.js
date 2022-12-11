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

import { orderBy } from 'lodash'
import moment from '@nextcloud/moment'
import { OptionsAPI } from '../../Api/options.js'
import { PublicAPI } from '../../Api/public.js'

const defaultOptions = () => ({
	list: [],
	ranked: false,
})

const namespaced = true
const state = defaultOptions()

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
		try {
			let response = null

			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getOptions(context.rootState.route.params.token)
			} else if (context.rootState.route.params.id) {
				response = await OptionsAPI.getOptions(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}

			context.commit('set', { options: response.data.options })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error loding options', { error: e.response }, { pollId: context.rootState.route.params.id })
			throw e
		}
	},

	async add(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.addOption(
					context.rootState.route.params.token,
					{
						pollId: context.rootState.route.params.id,
						timestamp: payload.timestamp,
						text: payload.text,
						duration: payload.duration,
					}
				)
			} else {
				response = await OptionsAPI.addOption(
					{
						pollId: context.rootState.route.params.id,
						timestamp: payload.timestamp,
						text: payload.text,
						duration: payload.duration,
					}
				)
			}
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error(`Error adding option: ${e.response.data}`, { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async update(context, payload) {
		try {
			const response = await OptionsAPI.updateOption(payload.option)
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			console.error('Error updating option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async delete(context, payload) {
		try {
			if (context.rootState.route.name === 'publicVote') {
				await PublicAPI.deleteOption(context.rootState.route.params.token, payload.option.id)
			} else {
				await OptionsAPI.deleteOption(payload.option.id)
			}
			context.commit('delete', { option: payload.option })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error deleting option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async addBulk(context, payload) {
		try {
			const response = OptionsAPI.addOptions(context.rootState.route.params.id, payload.text)
			context.commit('set', { options: response.data.options })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error(`Error adding option: ${e.response.data}`, { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async confirm(context, payload) {
		context.commit('confirm', { option: payload.option })
		try {
			const response = OptionsAPI.confirmOption(payload.option.id)
			context.commit('setItem', { option: response.data.option })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error confirming option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async reorder(context, payload) {
		context.commit('reorder', { options: payload })

		try {
			const response = await OptionsAPI.reorderOptions(context.rootState.route.params.id, payload)
			context.commit('set', { options: response.data.options })
		} catch (e) {
			console.error('Error reordering option', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async sequence(context, payload) {
		try {
			const response = await OptionsAPI.addOptionsSequence(
				payload.option.id,
				payload.sequence.step,
				payload.sequence.unit.value,
				payload.sequence.amount,
			)
			context.commit('set', { options: response.data.options })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error creating sequence', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async shift(context, payload) {
		try {
			const response = await OptionsAPI.shiftOptions(
				context.rootState.route.params.id,
				payload.shift.step,
				payload.shift.unit.value,
			)
			context.commit('set', { options: response.data.options })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error shifting dates', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},
}

export default { state, mutations, getters, actions, namespaced }
