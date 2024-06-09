/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, VotesAPI } from '../Api/index.js'
import { User } from '../Interfaces/interfaces.ts'
import { Logger } from '../helpers/index.js'
import { t } from '@nextcloud/l10n'
import { Option, useOptionsStore } from './options.ts'
import { usePollStore } from './poll.ts'
import { useRouterStore } from './router.ts'

export enum Answer {
	Yes = 'yes',
	No = 'no',
	Maybe = 'maybe',
}
export enum AnswerSymbol {
	Yes = '✔',
	Maybe = '❔',
	No = '❌',
}

export interface Vote {
	id: number
	pollId: number
	optionText: string
	answer: Answer
	answerSymbol: AnswerSymbol
	answerTranslated: string
	deleted: number
	optionId: number
	user: User
}

export interface Votes {
	list: Vote[]
}

export const useVotesStore = defineStore('votes', {
	state: (): Votes => ({
		list: [],
	}),

	getters: {
		countAllVotesByAnswer: (state) => (answer: Answer) => state.list.filter((vote) => vote.answer === answer).length,
		hasVotes: (state) => state.list.length > 0,
	
		getVote: (state) => (payload: { userId: string; option: { text: string } }) => {
			const found = state.list.find((vote) => (vote.user.userId === payload.userId
					&& vote.optionText === payload.option.text))
			if (found === undefined) {
				return {
					answer: '',
					optionText: payload.option.text,
					userId: payload.userId,
				}
			}
			return found
		},
	},

	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.getVotes(routerStore.params.token)
				} else if (routerStore.name === 'vote') {
					Logger.debug('Loading votes for poll', { pollId: routerStore.params.id })
					response = await VotesAPI.getVotes(routerStore.params.id)
				} else {
					this.$reset()
					return
				}
	
				const votes: Vote[] = []
				response.data.votes.forEach((vote: Vote) => {
					if (vote.answer === 'yes') {
						vote.answerTranslated = t('polls', 'Yes')
						vote.answerSymbol = AnswerSymbol.Yes
					} else if (vote.answer === 'maybe') {
						vote.answerTranslated = t('polls', 'Maybe')
						vote.answerSymbol = AnswerSymbol.Maybe
					} else {
						vote.answerTranslated = t('polls', 'No')
						vote.answerSymbol = AnswerSymbol.No
					}
					votes.push(vote)
				})

				this.list = votes
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
				throw error
			}
		},
	
		setItem(payload: { option: Option; vote: Vote }) {
			const index = this.list.findIndex((vote: Vote) =>
				vote.pollId === payload.option.pollId
				&& vote.user.userId === payload.vote.user.userId
				&& vote.optionText === payload.option.text)
			if (index > -1) {
				this.list[index] = Object.assign(this.list[index], payload.vote)
				return
			}
			this.list.push(payload.vote)
		},
	
		async set(payload: { option: Option; setTo: Answer }) {
			const routerStore = useRouterStore()
			const optionsStore = useOptionsStore()
			const pollStore = usePollStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.setVote(routerStore.params.token, payload.option.id, payload.setTo)
				} else {
					response = await VotesAPI.setVote(payload.option.id, payload.setTo)
				}
				this.setItem({ option: payload.option, vote: response.data.vote })
				optionsStore.load()
				pollStore.load()
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				if (error.response.status === 409) {
					this.load()
					optionsStore.load()
				} else {
					Logger.error('Error setting vote', { error, payload })
					throw error
				}
			}
		},
	
		async resetVotes() {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.removeVotes(routerStore.params.token)
				} else {
					response = await VotesAPI.removeUser(routerStore.params.id)
				}
				this.list = this.list.filter((vote: Vote) => vote.user.userId !== response.data.deleted)

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting votes', { error })
				throw error
			}
		},
	
		async deleteUser(payload) {
			const routerStore = useRouterStore()
			try {
				await VotesAPI.removeUser(routerStore.params.id, payload.userId)
				this.list = this.list.filter((vote: Vote) => vote.user.userId !== payload.userId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting votes', { error, payload })
				throw error
			}
		},

		async removeOrphanedVotes() {
			const routerStore = useRouterStore()
			const pollStore = usePollStore()
			const optionsStore = useOptionsStore()
			try {
				if (routerStore.name === 'publicVote') {
					await PublicAPI.removeOrphanedVotes(routerStore.params.token)
				} else {
					await VotesAPI.removeOrphanedVotes(routerStore.params.id)
				}
				pollStore.load()
				optionsStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting orphaned votes', { error })
				throw error
			}
		},
	
	},
})
