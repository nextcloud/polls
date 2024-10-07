/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, VotesAPI } from '../Api/index.js'
import { User } from '../Types/index.ts'
import { Logger } from '../helpers/index.ts'
import { t } from '@nextcloud/l10n'
import { Option, useOptionsStore } from './options.ts'
import { usePollStore } from './poll.ts'
import { useSessionStore } from './session.ts'

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
	answerTranslated: string
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

		getVote(payload: { userId: string; option: { text: string } }) {
			const found = this.list.find((vote: Vote) => (vote.user.id === payload.userId
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

		async load() {
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.getVotes(sessionStore.route.params.token)
				} else if (sessionStore.route.name === 'vote') {
					Logger.debug('Loading votes for poll', { pollId: sessionStore.route.params.id })
					response = await VotesAPI.getVotes(sessionStore.route.params.id)
				} else {
					this.$reset()
					return
				}
	
				const votes: Vote[] = []
				response.data.votes.forEach((vote: Vote) => {
					if (vote.answer === Answer.Yes) {
						vote.answerTranslated = t('polls', 'Yes')
						vote.answerSymbol = AnswerSymbol.Yes
					} else if (vote.answer === Answer.Maybe) {
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
				&& vote.user.id === payload.vote.user.id
				&& vote.optionText === payload.option.text)
			if (index > -1) {
				this.list[index] = Object.assign(this.list[index], payload.vote)
				return
			}
			this.list.push(payload.vote)
		},
	
		async set(payload: { option: Option; setTo: Answer }) {
			const sessionStore = useSessionStore()
			const optionsStore = useOptionsStore()
			const pollStore = usePollStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.setVote(sessionStore.route.params.token, payload.option.id, payload.setTo)
				} else {
					response = await VotesAPI.setVote(payload.option.id, payload.setTo)
				}
				this.setItem({ option: payload.option, vote: response.data.vote })
				optionsStore.list = response.data.options
				pollStore.$patch(response.data.poll)
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				if (error.response.status === 409) {
					this.load()
					optionsStore.load()
					pollStore.load()
				} else {
					Logger.error('Error setting vote', { error, payload })
					throw error
				}
			}
		},
	
		async resetVotes() {
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.removeVotes(sessionStore.route.params.token)
				} else {
					response = await VotesAPI.removeUser(sessionStore.route.params.id)
				}
				this.list = this.list.filter((vote: Vote) => vote.user.id !== response.data.deleted)

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting votes', { error })
				throw error
			}
		},
	
		async deleteUser(payload) {
			const sessionStore = useSessionStore()
			try {
				await VotesAPI.removeUser(sessionStore.route.params.id, payload.userId)
				this.list = this.list.filter((vote: Vote) => vote.user.id !== payload.userId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting votes', { error, payload })
				throw error
			}
		},

		async removeOrphanedVotes() {
			const sessionStore = useSessionStore()
			const pollStore = usePollStore()
			const optionsStore = useOptionsStore()
			try {
				if (sessionStore.route.name === 'publicVote') {
					await PublicAPI.removeOrphanedVotes(sessionStore.route.params.token)
				} else {
					await VotesAPI.removeOrphanedVotes(sessionStore.route.params.id)
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
