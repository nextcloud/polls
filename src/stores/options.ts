/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import orderBy from 'lodash/orderBy'

import { PublicAPI, OptionsAPI } from '../Api'
import { Logger } from '../helpers/modules/logger'

import { usePollStore } from './poll'
import { useSessionStore } from './session'
import { useVotesStore } from './votes'

import type { AxiosError } from '@nextcloud/axios'
import type { TimeUnitsType } from '../Types/dateTime'
import type {
	Sequence,
	SimpleOption,
	OptionDto,
	Option,
	OptionsStore,
	DateOptionFinder,
	HasIsoFields,
	OptionDurationMethod,
	OptionTimestampMethod,
} from './options.types'
import { DateTime, Duration } from 'luxon'

export const hydrateOption = (dto: OptionDto): Option => withLuxon(dto)

const withLuxon = <T extends HasIsoFields>(
	dto: T,
): T & OptionDurationMethod & OptionTimestampMethod => ({
	...dto,

	getDuration() {
		return Duration.fromISO(this.isoDuration ?? 'PT0S')
	},

	getDateTime() {
		return DateTime.fromISO(this.isoTimestamp ?? new Date().toISOString())
	},
})

export const useOptionsStore = defineStore('options', {
	state: (): OptionsStore => ({
		options: [],
		ranked: 'no',
	}),

	getters: {
		countAvailable(state): number {
			return state.options.filter(
				(option) => !option.locked && !option.deleted,
			).length
		},

		countVotedByCurrentUser(state): number {
			return state.options.filter(
				(option) => option.votes.currentUser === 'yes',
			).length
		},

		countOptionsLeft(): number {
			return this.countAvailable - this.countVotedByCurrentUser
		},

		rankedOptions(state): Option[] {
			return orderBy(
				state.options,
				['votes.yes', 'votes.maybe'],
				['desc', 'desc'],
			)
		},

		sortedOptions(state): Option[] {
			const pollStore = usePollStore()
			return pollStore.type === 'datePoll'
				? orderBy(state.options, ['order', 'duration'], ['asc', 'asc'])
				: state.options
		},

		orderedOptions(state): Option[] {
			return state.ranked === 'yes' ? this.rankedOptions : this.sortedOptions
		},

		confirmed(state): Option[] {
			return state.options.filter((option) => option.confirmed > 0)
		},

		countProposals(state): number {
			return state.options.filter((option: Option) => option.owner !== null)
				.length
		},
	},

	actions: {
		optionsDtoToOptions(optionsDto: OptionDto[]): Option[] {
			return optionsDto.map(hydrateOption)
		},

		find(option: DateOptionFinder): Option | undefined {
			if (option) {
				return this.options.find(
					(opt) =>
						opt.isoTimestamp === option.isoTimestamp
						&& opt.isoDuration === option.isoDuration,
				)
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

				this.options = this.optionsDtoToOptions(response.data.options)
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

		updateOption(payload: { option: OptionDto }) {
			const hydrated = hydrateOption(payload.option)
			const index = this.options.findIndex(
				(option) => option.id === hydrated.id,
			)

			if (index < 0) {
				this.options.push(hydrated)
			} else {
				this.options.splice(index, 1, hydrated)
			}
			this.options.sort((a, b) =>
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

				this.options = this.optionsDtoToOptions(response.data.options)

				if (response.data.votes) {
					const votesStore = useVotesStore()
					votesStore.votes = response.data.votes
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
				this.options = this.optionsDtoToOptions(response.data.options)
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
			const index = this.options.findIndex(
				(option: Option) => option.id === payload.option.id,
			)
			this.options[index].confirmed = Math.abs(
				this.options[index].confirmed - 1,
			)

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

			this.options.splice(newIndex, 0, this.options.splice(oldIndex, 1)[0])

			try {
				const response = await OptionsAPI.reorderOptions(
					sessionStore.currentPollId,
					this.options.map(({ id, text }) => ({
						id,
						text,
					})),
				)
				this.options = this.optionsDtoToOptions(response.data.options)
			} catch (error) {
				Logger.error('Error reordering option', {
					error,
					options: this.options,
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
				this.options = this.optionsDtoToOptions(response.data.options)
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
				this.options = this.optionsDtoToOptions(response.data.options)
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
