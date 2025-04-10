/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, OptionsAPI } from '../Api/index.ts'
import { User } from '../Types/index.ts'
import { Logger } from '../helpers/index.ts'
import moment from '@nextcloud/moment'
import orderBy from 'lodash/orderBy'
import { usePollStore, PollType } from './poll.ts'
import { useSessionStore } from './session.ts'
import { Answer, useVotesStore } from './votes.ts'
import {
	DateTimeDetails,
	DateTimeUnitType,
	TimeUnitsType,
} from '../constants/dateUnits.ts'
import { AxiosError } from '@nextcloud/axios'

export enum RankedType {
	ranked = 'yes',
	notRanked = 'no',
}

export type Sequence = {
	unit: DateTimeUnitType
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
	owner: User | undefined
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

		explodeDates(option: Option): {
			from: DateTimeDetails
			to: DateTimeDetails
			raw: string
			iso: string
			dayLong: boolean
		} {
			const from = moment.unix(option.timestamp)
			const to = moment.unix(option.timestamp + Math.max(0, option.duration))
			// does the event start at 00:00 local time and
			// is the duration divisable through 24 hours without rest
			// then we have a day long event (one or multiple days)
			// In this case we want to suppress the display of any time information
			const dayLongEvent =
				from.unix() === moment(from).startOf('day').unix()
				&& to.unix() === moment(to).startOf('day').unix()
				&& from.unix() !== to.unix()

			const dayModifier = dayLongEvent ? 1 : 0
			// modified to date, in case of day long events, a second gets substracted
			// to set the begin of the to day to the end of the previous date
			const toModified = moment(to).subtract(dayModifier, 'days')

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
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.getOptions(
							sessionStore.route.params.token as string,
						)
					}
					if (sessionStore.currentPollId) {
						return OptionsAPI.getOptions(sessionStore.currentPollId)
					}
					return null
				})()

				if (!response) {
					this.$reset()
					return
				}

				this.list = response.data.options
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error loding options', {
					error,
					pollId: sessionStore.currentPollId,
				})
				throw error
			}
		},

		updateOption(payload: { option: Option }) {
			const index = this.list.findIndex(
				(option) => option.id === payload.option.id,
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

		async add(
			simpleOption: SimpleOption,
			sequence: Sequence | null = null,
			voteYes: boolean = false,
		) {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.addOption(
							sessionStore.route.params.token,
							simpleOption,
							sequence,
							voteYes,
						)
					}
					return OptionsAPI.addOption(
						sessionStore.currentPollId,
						simpleOption,
						sequence,
						voteYes,
					)
				})()

				this.list = response.data.options

				if (response.data.votes) {
					const votesStore = useVotesStore()
					votesStore.list = response.data.votes
				}
			} catch (error) {
				if ((error as AxiosError)?.code !== 'ERR_CANCELED') {
					Logger.error('Error adding option', {
						error,
						simpleOption,
					})
					this.load()
					throw error
				}
			}
		},

		async update(payload: { option: Option }) {
			try {
				const response = await OptionsAPI.updateOption(payload.option)
				this.updateOption({ option: response.data.option })
			} catch (error) {
				Logger.error('Error updating option', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async delete(payload: { option: Option }) {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.deleteOption(
							sessionStore.route.params.token,
							payload.option.id,
						)
					}
					return OptionsAPI.deleteOption(payload.option.id)
				})()

				this.updateOption({ option: response.data.option })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting option', {
					error,
					payload,
				})
				throw error
			}
		},

		async restore(payload: { option: Option }) {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.restoreOption(
							sessionStore.route.params.token,
							payload.option.id,
						)
					}
					return OptionsAPI.restoreOption(payload.option.id)
				})()

				this.updateOption({ option: response.data.option })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error restoring option', {
					error,
					payload,
				})
				throw error
			}
		},

		async addBulk(payload: { text: string }) {
			const sessionStore = useSessionStore()
			try {
				const response = await OptionsAPI.addOptions(
					sessionStore.currentPollId,
					payload.text,
				)
				this.list = response.data.options
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error adding option', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async confirm(payload: { option: Option }) {
			const index = this.list.findIndex(
				(option: Option) => option.id === payload.option.id,
			)
			this.list[index].confirmed = Math.abs(this.list[index].confirmed - 1)

			try {
				const response = await OptionsAPI.confirmOption(payload.option.id)
				this.updateOption({ option: response.data.option })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error confirming option', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async changeOrder(oldIndex: number, newIndex: number) {
			const sessionStore = useSessionStore()

			this.list.splice(newIndex, 0, this.list.splice(oldIndex, 1)[0])

			try {
				const response = await OptionsAPI.reorderOptions(
					sessionStore.currentPollId,
					this.list.map(({ id, text }) => ({
						id,
						text,
					})),
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
					payload.sequence,
				)
				this.list = response.data.options
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error creating sequence', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async shift(payload: { shift: TimeUnitsType }) {
			const sessionStore = useSessionStore()
			try {
				const response = await OptionsAPI.shiftOptions(
					sessionStore.currentPollId,
					payload.shift.value,
					payload.shift.unit.id,
				)
				this.list = response.data.options
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error shifting dates', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},
	},
})
