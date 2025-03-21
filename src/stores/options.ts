/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, OptionsAPI } from '../Api/index.js'
import { User } from '../Types/index.ts'
import { Logger } from '../helpers/index.ts'
import moment from '@nextcloud/moment'
import orderBy from 'lodash/orderBy'
import { usePollStore, PollType } from './poll.ts'
import { useSessionStore } from './session.ts'
import { Answer } from './votes.ts'
import { DateUnitType, TimeUnitsType } from '../constants/dateUnits.ts'

export enum RankedType {
	ranked = 'yes',
	notRanked = 'no',
}

export type Sequence = {
	unit: DateUnitType
	stepWidth: number
	repetitions: number
}

export type OptionVotes = {
	yes: number
	maybe: number
	no: number
	count: number
	currentUser?: Answer
}

export type SimpleOption = {
	text?: string
	timestamp?: number
	duration?: number
}

export type Option = {
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
	isOwner: boolean
	votes: OptionVotes
	owner: User | null
}

export type Options = {
	list: Option[]
	ranked: RankedType
}

export const useOptionsStore = defineStore('options', {
	state: (): Options => ({
		list: [],
		ranked: RankedType.notRanked,
	}),

	getters: {
		count(state): number {
			return state.list.length
		},

		rankedOptions(state): Option[] {
			return orderBy(
				state.list,
				['votes.yes', 'votes.maybe'],
				['desc', 'desc'],
			)
		},

		sortedOptions(state): Option[] {
			const pollStore = usePollStore()
			return pollStore.type === PollType.Date
				? orderBy(state.list, ['timestamp'], ['asc'])
				: state.list
		},

		orderedOptions(state): Option[] {
			return state.ranked === 'yes' ? this.rankedOptions : this.sortedOptions
		},

		confirmed(state): Option[] {
			return state.list.filter((option) => option.confirmed > 0)
		},
	},

	actions: {
		find(timestamp: number, duration: number): Option | undefined {
			return this.list.find(
				(option) =>
					option.timestamp === timestamp && option.duration === duration,
			)
		},

		explodeDates(option: Option) {
			const from = moment.unix(option.timestamp)
			const to = moment.unix(option.timestamp + Math.max(0, option.duration))
			// does the event start at 00:00 local time and
			// is the duration divisable through 24 hours without rest
			// then we have a day long event (one or multiple days)
			// In this case we want to suppress the display of any time information
			const dayLongEvent =
				from.unix() === moment(from).startOf('day').unix() &&
				to.unix() === moment(to).startOf('day').unix() &&
				from.unix() !== to.unix()

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
					month: from.format(
						moment().year() === from.year() ? 'MMM' : "MMM [ ']YY",
					),
					day: from.format('D'),
					dow: from.format('ddd'),
					time: from.format('LT'),
					date: from.format('ll'),
					dateTime: from.format('llll'),
					iso: moment(from).toISOString(),
					utc: moment(from).utc().format('llll'),
				},
				to: {
					month: toModified.format(
						moment().year() === toModified.year() ? 'MMM' : "MMM [ ']YY",
					),
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

		async load() {
			const sessionStore = useSessionStore()
			try {
				let response = null

				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.getOptions(
						sessionStore.route.params.token,
					)
				} else if (sessionStore.route.params.id) {
					response = await OptionsAPI.getOptions(
						sessionStore.route.params.id,
					)
				} else {
					this.$reset()
					return
				}

				this.list = response.data.options
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loding options', {
					error,
					pollId: sessionStore.route.params.id,
				})
				throw error
			}
		},

		updateOption(payload: { option: Option }) {
			const index = this.list.findIndex(
				(option) => parseInt(option.id) === payload.option.id,
			)

			if (index < 0) {
				this.list.push(payload.option)
			} else {
				this.list.splice(index, 1, payload.option)
			}
			this.list.sort((a, b) =>
				a.order < b.order ? -1 : a.order > b.order ? 1 : 0,
			)
		},

		async add(payload: SimpleOption) {
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.addOption(
						sessionStore.route.params.token,
						{
							pollId: sessionStore.route.params.id,
							timestamp: payload.timestamp,
							text: payload.text,
							duration: payload.duration,
						},
					)
				} else {
					response = await OptionsAPI.addOption({
						pollId: sessionStore.route.params.id,
						timestamp: payload.timestamp,
						text: payload.text,
						duration: payload.duration,
					})
				}
				this.list.push(response.data.option)
				return response.data.option
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
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.deleteOption(
						sessionStore.route.params.token,
						payload.option.id,
					)
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
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.restoreOption(
						sessionStore.route.params.token,
						payload.option.id,
					)
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
			const sessionStore = useSessionStore()
			try {
				const response = await OptionsAPI.addOptions(
					sessionStore.route.params.id,
					payload.text,
				)
				this.list = response.data.options
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error adding option', { error, payload })
				this.load()
				throw error
			}
		},

		confirmOption(payload: { option: Option }) {
			const index = this.list.findIndex(
				(option: Option) => option.id === payload.option.id,
			)

			this.list[index].confirmed = !this.list[index].confirmed
		},

		async confirm(payload: { option: Option }) {
			const index = this.list.findIndex(
				(option: Option) => option.id === payload.option.id,
			)
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

		async changeOrder(oldIndex: number, newIndex: number) {
			const sessionStore = useSessionStore()

			this.list.splice(newIndex, 0, this.list.splice(oldIndex, 1)[0])

			try {
				const response = await OptionsAPI.reorderOptions(
					sessionStore.route.params.id,
					this.list.map(({ id, text }) => ({ id, text })),
				)
				this.list = response.data.options
			} catch (error) {
				Logger.error('Error reordering option', {
					error,
					options: this.list,
					oldIndex,
					newIndex,
				})
				this.load()
				throw error
			}
		},

		async sequence(payload: { option: Option; sequence: Sequence }) {
			try {
				const response = await OptionsAPI.addOptionsSequence(
					payload.option.id,
					payload.sequence.stepWidth,
					payload.sequence.unit.key,
					payload.sequence.repetitions,
				)
				this.list = response.data.options
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error creating sequence', { error, payload })
				this.load()
				throw error
			}
		},

		async shift(payload: { shift: TimeUnitsType }) {
			const sessionStore = useSessionStore()
			try {
				const response = await OptionsAPI.shiftOptions(
					sessionStore.route.params.id,
					payload.shift.value,
					payload.shift.unit.key,
				)
				this.list = response.data.options
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error shifting dates', { error, payload })
				this.load()
				throw error
			}
		},
	},
})
