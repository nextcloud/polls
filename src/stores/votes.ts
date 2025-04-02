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
}

export const useVotesStore = defineStore('votes', {
	state: (): Votes => ({
		list: [],
	}),

	getters: {
		hasVotes: (state) => state.list.length > 0,
	},

	actions: {
		countAllVotesByAnswer(answer: Answer): number {
			return this.list.filter((vote) => vote.answer === answer).length
		},

		getVote(payload: { user: User; option: Option }): Vote {
			const found = this.list.find(
				(vote: Vote) =>
					vote.user.id === payload.user.id &&
					vote.optionText === payload.option.text,
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
					vote.pollId === payload.option.pollId &&
					vote.user.id === payload.vote.user.id &&
					vote.optionText === payload.option.text,
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
