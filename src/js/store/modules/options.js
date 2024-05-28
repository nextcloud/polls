/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { orderBy } from 'lodash'
import moment from '@nextcloud/moment'
import { OptionsAPI, PublicAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

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
		const index = state.list.findIndex((option) =>
			parseInt(option.id) === payload.option.id,
		)

		if (index < 0) {
			state.list.push(payload.option)
		} else {
			state.list.splice(index, 1, payload.option)
		}
		state.list.sort((a, b) => (a.order < b.order) ? -1 : (a.order > b.order) ? 1 : 0)
	},
}

const getters = {
	count: (state) => state.list.length,
	rankedOptions: (state) => state.ranked ? orderBy(state.list, ['votes.yes', 'votes.maybe'], ['desc', 'desc']) : state.list,
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
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error loding options', { error, pollId: context.rootState.route.params.id })
			throw error
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
					},
				)
			} else {
				response = await OptionsAPI.addOption(
					{
						pollId: context.rootState.route.params.id,
						timestamp: payload.timestamp,
						text: payload.text,
						duration: payload.duration,
					},
				)
			}
			context.commit('setItem', { option: response.data.option })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error adding option', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async update(context, payload) {
		try {
			const response = await OptionsAPI.updateOption(payload.option)
			context.commit('setItem', { option: response.data.option })
		} catch (error) {
			Logger.error('Error updating option', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async delete(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.deleteOption(context.rootState.route.params.token, payload.option.id)
			} else {
				response = await OptionsAPI.deleteOption(payload.option.id)
			}
			context.commit('setItem', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting option', { error, payload })
			throw error
		}
	},

	async restore(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.restoreOption(context.rootState.route.params.token, payload.option.id)
			} else {
				response = await OptionsAPI.restoreOption(payload.option.id)
			}
			context.commit('setItem', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error restoring option', { error, payload })
			throw error
		}
	},

	async addBulk(context, payload) {
		try {
			const response = await OptionsAPI.addOptions(context.rootState.route.params.id, payload.text)
			context.commit('set', { options: response.data.options })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error adding option', { error,  payload })
			context.dispatch('list')
			throw error
		}
	},

	async confirm(context, payload) {
		context.commit('confirm', { option: payload.option })
		try {
			const response = await OptionsAPI.confirmOption(payload.option.id)
			context.commit('setItem', { option: response.data.option })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error confirming option', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async reorder(context, payload) {
		context.commit('reorder', { options: payload })

		try {
			const response = await OptionsAPI.reorderOptions(context.rootState.route.params.id, payload)
			context.commit('set', { options: response.data.options })
		} catch (error) {
			Logger.error('Error reordering option', { error, payload })
			context.dispatch('list')
			throw error
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
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error creating sequence', { error, payload })
			context.dispatch('list')
			throw error
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
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error shifting dates', { error, payload })
			context.dispatch('list')
			throw error
		}
	},
}

export default { state, mutations, getters, actions, namespaced }
