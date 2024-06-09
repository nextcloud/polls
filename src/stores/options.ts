/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, OptionsAPI } from '../Api/index.js'
import { User } from '../Interfaces/interfaces.ts'
import { Logger } from '../helpers/index.js'
import moment from '@nextcloud/moment'
import { orderBy } from 'lodash/orderBy'
import { usePollStore, PollType } from './poll.ts'
import { useRouterStore } from './router.ts'

interface Sequence {
	step: number
	unit: { value: number }
	amount: number
}

interface Shift {
	step: number
	unit: { value: number }	
}

export interface Option {
	id: number
	pollId: number
	text: string
	timestamp: number
	deleted: number
	order: number
	confirmed: number
	duration: number
	locked: boolean
	hash: string
	votes: number
	owner: User

}

interface Options {
	list: Option[]
	ranked: boolean
}

export const useOptionsStore = defineStore('options', {
	state: (): Options => ({
		list: [],
		ranked: false,
	}),

	getters: {
		count(state) {
			return state.list.length
		},

		rankedOptions(state) {
			return state.ranked ? orderBy(state.list, ['votes.yes', 'votes.maybe'], ['desc', 'desc']) : state.list
		},

		proposalsExist(state) {
			return !!state.list.filter((option) => option.owner.userId).length
		},

		confirmed(state) {
			return state.list.filter((option) => option.confirmed > 0)
		},
	
		explodeDates: () => (option) => {
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
			const pollStore = usePollStore()

			if (pollStore.type !== PollType.Date) {
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
	},

	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				let response = null
	
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.getOptions(routerStore.params.token)
				} else if (routerStore.params.id) {
					response = await OptionsAPI.getOptions(routerStore.params.id)
				} else {
					this.$reset()
					return
				}

				this.list = response.data.options
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loding options', { error, pollId: routerStore.params.id })
				throw error
			}
		},
	
		updateOption(payload: { option: Option }) {
			const index = this.list.findIndex((option) =>
				parseInt(option.id) === payload.option.id,
			)
	
			if (index < 0) {
				this.list.push(payload.option)
			} else {
				this.list.splice(index, 1, payload.option)
			}
			this.list.sort((a, b) => (a.order < b.order) ? -1 : (a.order > b.order) ? 1 : 0)
		},
	
		async add(payload: { timestamp: number; text: string; duration: number }) {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.addOption(
						routerStore.params.token,
						{
							pollId: routerStore.params.id,
							timestamp: payload.timestamp,
							text: payload.text,
							duration: payload.duration,
						},
					)
				} else {
					response = await OptionsAPI.addOption(
						{
							pollId: routerStore.params.id,
							timestamp: payload.timestamp,
							text: payload.text,
							duration: payload.duration,
						},
					)
				}
				this.$patch({ option: response.data.option })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error adding option', { error, payload })
				this.load()
				throw error
			}
		},
	
		async update(payload: { option: Option }) {
			try {
				const response = await OptionsAPI.updateOption(payload.option)
				this.updateOption({ option: response.data.option })
			} catch (error) {
				Logger.error('Error updating option', { error, payload })
				this.load()
				throw error
			}
		},
	
		async delete(payload: { option: Option }) {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.deleteOption(routerStore.params.token, payload.option.id)
				} else {
					response = await OptionsAPI.deleteOption(payload.option.id)
				}
				this.updateOption({ option: response.data.option })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting option', { error, payload })
				throw error
			}
		},
	
		async restore(payload: { option: Option }) {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.restoreOption(routerStore.params.token, payload.option.id)
				} else {
					response = await OptionsAPI.restoreOption(payload.option.id)
				}
				this.updateOption({ option: response.data.option })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error restoring option', { error, payload })
				throw error
			}
		},
	
		async addBulk(payload: { text: string }) {
			const routerStore = useRouterStore()
			try {
				const response = await OptionsAPI.addOptions(routerStore.params.id, payload.text)
				this.$patch({ options: response.data.options })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error adding option', { error, payload })
				this.load()
				throw error
			}
		},
	
		confirmOption(payload: { option: Option }) {
			const index = this.list.findIndex((option: Option) => option.id === payload.option.id)
	
			this.list[index].confirmed = !this.list[index].confirmed
		},
	
		async confirm(payload: { option: Option }) {
			const index = this.list.findIndex((option: Option) => option.id === payload.option.id)
			this.list[index].confirmed = !this.list[index].confirmed
	
			try {
				const response = await OptionsAPI.confirmOption(payload.option.id)
				this.updateOption({ option: response.data.option })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error confirming option', { error, payload })
				this.load()
				throw error
			}
		},
	
		async reorder(payload: { options: Option[] }) {
			const routerStore = useRouterStore()
			payload.options.forEach((item, i) => {
				item.order = i + 1
			})
			this.list = payload.options
	
			try {
				const response = await OptionsAPI.reorderOptions(routerStore.params.id, payload)
				this.$patch({ options: response.data.options })
			} catch (error) {
				Logger.error('Error reordering option', { error, payload })
				this.load()
				throw error
			}
		},
	
		async sequence(payload: { option: Option; sequence: Sequence }) {
			try {
				const response = await OptionsAPI.addOptionsSequence(
					payload.option.id,
					payload.sequence.step,
					payload.sequence.unit.value,
					payload.sequence.amount,
				)
				this.$patch({ options: response.data.options })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error creating sequence', { error, payload })
				this.load()
				throw error
			}
		},
	
		async shift(payload: { shift: Shift }) {
			const routerStore = useRouterStore()
			try {
				const response = await OptionsAPI.shiftOptions(
					routerStore.params.id,
					payload.shift.step,
					payload.shift.unit.value,
				)
				this.$patch({ options: response.data.options })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error shifting dates', { error, payload })
				this.load()
				throw error
			}
		},
	},
})
