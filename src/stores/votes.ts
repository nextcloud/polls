/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, VotesAPI } from '../Api/index.ts'
import { User } from '../Types/index.ts'
import { Logger, StoreHelper } from '../helpers/index.ts'
import { Option } from './options.ts'
import { usePollStore } from './poll.ts'
import { useSessionStore } from './session.ts'
import { AxiosError } from '@nextcloud/axios'

export enum Answer {
	Yes = 'yes',
	No = 'no',
	Maybe = 'maybe',
	None = '',
}
export enum AnswerSymbol {
	Yes = '✔',
	Maybe = '❔',
	No = '❌',
	None = '',
}
const answerSortOrder: { [key in Answer]: number } = {
	[Answer.Yes]: 1,
	[Answer.Maybe]: 2,
	[Answer.No]: 3,
	[Answer.None]: 4,
}
export type Vote = {
	id: number
	pollId: number
	optionText: string
	answer: Answer
	answerSymbol: AnswerSymbol
	deleted: number
	optionId: number
	user: User
}

export type Votes = {
	list: Vote[]
	sortByOption: number
}

export const useVotesStore = defineStore('votes', {
	state: (): Votes => ({
		list: [],
		sortByOption: 0,
	}),

	getters: {
		sortedVotes(state): Vote[] {
			const sessionStore = useSessionStore()
			const pollStore = usePollStore()

			// add a fake vote for the current user, if not among participants and voting is allowed
			if (
				!state.list.find(
					(vote: Vote) => vote.user.id === sessionStore.currentUser?.id,
				)
				&& sessionStore.currentUser?.id
				&& pollStore.permissions.vote
			) {
				state.list.unshift({
					answer: Answer.None,
					optionText: '',
					user: sessionStore.currentUser,
					answerSymbol: AnswerSymbol.None,
					deleted: 0,
					id: 0,
					optionId: state.sortByOption,
					pollId: pollStore.id,
				})
			}

			if (state.sortByOption === 0) {
				return state.list.sort((a, b) => {
					// sort votes of the current user to the top
					if (
						a.user.id === sessionStore.currentUser.id
						&& b.user.id !== sessionStore.currentUser.id
					) {
						return -1
					}
					if (
						b.user.id === sessionStore.currentUser.id
						&& a.user.id !== sessionStore.currentUser.id
					) {
						return 1
					}
					// sort other votes by display name
					if (a.user.displayName < b.user.displayName) {
						return -1
					}
					if (a.user.displayName > b.user.displayName) {
						return 1
					}
					return 0
				})
			}

			if (state.sortByOption > 0) {
				return state.list.sort((a, b) => {
					// first sort by optionId (the closer the searched optionId, the further in front)
					if (
						a.optionId === state.sortByOption
						&& b.optionId !== state.sortByOption
					) {
						return -1
					}
					if (
						b.optionId === state.sortByOption
						&& a.optionId !== state.sortByOption
					) {
						return 1
					}

					// then sort by answers with wanted order
					return answerSortOrder[a.answer] - answerSortOrder[b.answer]
				})
			}

			// fallback: no sort
			return state.list
		},

		hasVotes: (state) => state.list.length > 0,
	},

	actions: {
		countAllVotesByAnswer(answer: Answer): number {
			return this.list.filter((vote) => vote.answer === answer).length
		},

		getVote(payload: { user: User; option: Option }): Vote {
			const found = this.list.find(
				(vote: Vote) =>
					vote.user.id === payload.user.id
					&& vote.optionText === payload.option.text,
			)
			if (found === undefined) {
				return {
					answer: Answer.None,
					optionText: payload.option.text,
					user: payload.user,
					answerSymbol: AnswerSymbol.None,
					deleted: 0,
					id: 0,
					optionId: payload.option.id,
					pollId: payload.option.pollId,
				}
			}
			return found
		},

		async load() {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.getVotes(sessionStore.route.params.token)
					}
					if (sessionStore.route.name === 'vote') {
						return VotesAPI.getVotes(sessionStore.currentPollId)
					}

					return null
				})()

				if (!response) {
					this.$reset()
					return
				}

				this.list = response.data.votes
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.$reset()
				throw error
			}
		},

		setItem(payload: { option: Option; vote: Vote }) {
			const index = this.list.findIndex(
				(vote: Vote) =>
					vote.pollId === payload.option.pollId
					&& vote.user.id === payload.vote.user.id
					&& vote.optionText === payload.option.text,
			)
			if (index > -1) {
				this.list[index] = Object.assign(this.list[index], payload.vote)
				return
			}
			this.list.push(payload.vote)
		},

		async set(payload: { option: Option; setTo: Answer }) {
			const sessionStore = useSessionStore()
			const pollStore = usePollStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.setVote(
							sessionStore.route.params.token,
							payload.option.id,
							payload.setTo,
						)
					}
					return VotesAPI.setVote(payload.option.id, payload.setTo)
				})()

				this.setItem({
					option: payload.option,
					vote: response.data.vote,
				})
				StoreHelper.updateStores(response.data)

				return response
			} catch (e) {
				const error = e as AxiosError
				if (error?.code === 'ERR_CANCELED') {
					return
				}
				if (error.response?.status === 409) {
					pollStore.load()
					throw error
				} else {
					Logger.error('Error setting vote aa', {
						error,
						payload,
					})
					throw error
				}
			}
		},

		async setSort(payload: { optionId: number }) {
			this.sortByOption = payload.optionId
		},

		async resetVotes() {
			Logger.debug('Resetting votes')
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.resetVotes(sessionStore.route.params.token)
					}
					return VotesAPI.resetVotes(sessionStore.currentPollId)
				})()

				StoreHelper.updateStores(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting votes', { error })
				throw error
			}
		},

		async resetUserVotes(payload: { userId: string }) {
			const sessionStore = useSessionStore()
			try {
				const response = await VotesAPI.resetVotes(
					sessionStore.currentPollId,
					payload.userId,
				)
				StoreHelper.updateStores(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting votes', {
					error,
					payload,
				})
				throw error
			}
		},

		async removeOrphanedVotes() {
			const sessionStore = useSessionStore()
			const pollStore = usePollStore()
			try {
				if (sessionStore.route.name === 'publicVote') {
					await PublicAPI.removeOrphanedVotes(
						sessionStore.route.params.token,
					)
				} else {
					await VotesAPI.removeOrphanedVotes(sessionStore.currentPollId)
				}
				pollStore.load()
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting orphaned votes', { error })
				throw error
			}
		},
	},
})
