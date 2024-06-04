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

export type Answer = 'yes' | 'no' | 'maybe'
export type AnswerSymbol = '✔' | '❔' | '❌'

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

const optionsStore = useOptionsStore()
const pollsStore = usePollStore()

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
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.getVotes(this.$router.route.params.token)
				} else if (this.$router.route.name === 'vote') {
					response = await VotesAPI.getVotes(this.$router.route.params.id)
				} else {
					this.$reset()
					return
				}
	
				const votes = []
				response.data.votes.forEach((vote: Vote) => {
					if (vote.answer === 'yes') {
						vote.answerTranslated = t('polls', 'Yes')
						vote.answerSymbol = '✔'
					} else if (vote.answer === 'maybe') {
						vote.answerTranslated = t('polls', 'Maybe')
						vote.answerSymbol = '❔'
					} else {
						vote.answerTranslated = t('polls', 'No')
						vote.answerSymbol = '❌'
					}
					votes.push(vote)
				})
				this.$patch(votes)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
				throw error
			}
		},
	
		setItem(payload) {
			const index = this.list.findIndex((vote: Vote) =>
				vote.pollId === payload.pollId
				&& vote.user.userId === payload.vote.user.userId
				&& vote.optionText === payload.option.text)
			if (index > -1) {
				this.list[index] = Object.assign(this.list[index], payload.vote)
				return
			}
			this.list.push(payload.vote)
		},
	
		async set(payload: { option: Option; setTo: Answer }) {
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.setVote(this.$router.route.params.token, payload.option.id, payload.setTo)
				} else {
					response = await VotesAPI.setVote(payload.option.id, payload.setTo)
				}
				this.setItem({ option: payload.option, pollId: this.$router.poll.id, vote: response.data.vote })
				optionsStore.load()
				pollsStore.load()
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
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.removeVotes(this.$router.route.params.token)
				} else {
					response = await VotesAPI.removeUser(this.$router.route.params.id)
				}
				this.list = this.list.filter((vote: Vote) => vote.user.userId !== response.data.deleted)

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting votes', { error })
				throw error
			}
		},
	
		async deleteUser(payload) {
			try {
				await VotesAPI.removeUser(this.$router.route.params.id, payload.userId)
				this.list = this.list.filter((vote: Vote) => vote.user.userId !== payload.userId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting votes', { error, payload })
				throw error
			}
		},

		async removeOrphanedVotes() {
			try {
				if (this.$router.route.name === 'publicVote') {
					await PublicAPI.removeOrphanedVotes(this.$router.route.params.token)
				} else {
					await VotesAPI.removeOrphanedVotes(this.$router.route.params.id)
				}
				pollsStore.load()
				optionsStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting orphaned votes', { error })
				throw error
			}
		},
	
	},
})
