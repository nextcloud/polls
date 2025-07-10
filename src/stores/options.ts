/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, OptionsAPI } from '../Api/index.ts'
import { User } from '../Types/index.ts'
import { Logger } from '../helpers/index.ts'
import orderBy from 'lodash/orderBy'
import { usePollStore, PollType } from './poll.ts'
import { useSessionStore } from './session.ts'
import { Answer, useVotesStore } from './votes.ts'
import { DateTimeUnitType, TimeUnitsType } from '../constants/dateUnits.ts'
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
	options: Option[]
	ranked: RankedType
}

export const useOptionsStore = defineStore('options', {
	state: (): Options => ({
		options: [],
		ranked: RankedType.notRanked,
	}),

	getters: {
		countAvailable(state): number {
			return state.options.filter(
				(option) => !option.locked && !option.deleted,
			).length
		},

		countVotedByCurrentUser(state): number {
			return state.options.filter(
				(option) => option.votes.currentUser === Answer.Yes,
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
			return pollStore.type === PollType.Date
				? orderBy(state.options, ['timestamp', 'duration'], ['asc', 'asc'])
				: state.options
		},

		orderedOptions(state): Option[] {
			return state.ranked === 'yes' ? this.rankedOptions : this.sortedOptions
		},

		confirmed(state): Option[] {
			return state.options.filter((option) => option.confirmed > 0)
		},

		countProposals(state): number {
			 return state.options.filter((option: Option) => option.owner !== null).length;

		},
	},

	actions: {
		find(timestamp: number, duration: number): Option | undefined {
			return this.options.find(
				(option) =>
					option.timestamp === timestamp && option.duration === duration,
			)
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

				this.options = response.data.options
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
			const index = this.options.findIndex(
				(option) => option.id === payload.option.id,
			)

			if (index < 0) {
				this.options.push(payload.option)
			} else {
				this.options.splice(index, 1, payload.option)
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

				this.options = response.data.options

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
				this.options = response.data.options
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
				this.options = response.data.options
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
				this.options = response.data.options
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
				this.options = response.data.options
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
