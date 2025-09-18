/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, VotesAPI } from '../Api'
import { Logger } from '../helpers/modules/logger'
import { StoreHelper } from '../helpers/modules/StoreHelper'

import { usePollStore } from './poll'
import { useSessionStore } from './session'
import { useOptionsStore } from './options'

import type { AxiosError } from '@nextcloud/axios'
import type { User } from '../Types'
import type { Option } from './options.types'
import type { Answer, Vote, VotesStore } from './votes.types'

const answerSortOrder: { [key in Answer]: number } = {
	yes: 1,
	maybe: 2,
	no: 3,
	'': 3,
}

export const useVotesStore = defineStore('votes', {
	state: (): VotesStore => ({
		votes: [],
		sortByOption: 0,
		meta: {
			chunks: {
				size: 25,
				loaded: 1,
			},
		},
	}),

	getters: {
		/**
		 * Returns a unique list of actual participants (which actually have voted)
		 * sorted by display name
		 * @param state
		 * @return
		 */
		participants: (state): User[] =>
			Array.from(
				new Map(
					state.votes.map((vote) => [vote.user.id, vote.user]),
				).values(),
			).sort((aUser, bUser) => {
				if (aUser.displayName < bUser.displayName) {
					return -1
				}
				if (aUser.displayName > bUser.displayName) {
					return 1
				}
				return 0
			}),

		loadedParticipants(state: VotesStore): number {
			return Math.min(
				state.meta.chunks.loaded * state.meta.chunks.size,
				this.participants.length,
			)
		},

		countHiddenParticipants(): number {
			return this.participants.length - this.chunkedParticipants.length
		},

		/**
		 * Returns a chunked list of participants, limited by the performance threshold
		 * If the number of participants is greater than the performance threshold, only return the loaded chunks
		 * @param state
		 * @return
		 */
		chunkedParticipants(state): User[] {
			const sessionStore = useSessionStore()
			const pollStore = usePollStore()
			if (pollStore.viewMode === 'list-view') {
				return [sessionStore.currentUser]
			}

			if (
				this.participants.length > state.meta.chunks.size
				&& this.participants.length
					> state.meta.chunks.loaded * state.meta.chunks.size
			) {
				// if the number of cells is greater than the performance threshold, return an empty array
				return this.sortedParticipants.slice(
					0,
					state.meta.chunks.loaded * state.meta.chunks.size,
				)
			}
			// otherwise return all participants
			return this.sortedParticipants
		},

		/**
		 * Returns a sorted list of participants including the current user, even if not voted
		 * Sorting is done by display name (default) or by votes for the selected option (if sortByOption is set)
		 * @param state
		 * @return
		 */
		sortedParticipants(state): User[] {
			const sessionStore = useSessionStore()
			const pollStore = usePollStore()

			// clone the actual participants to avoid mutating the original list
			const participants = Array.from(this.participants)

			// find the current user
			const currentUserIndex = participants.findIndex(
				(user) => user.id === sessionStore.currentUser?.id,
			)

			if (currentUserIndex < 0 && !pollStore.status.isExpired) {
				// add current user to the begining of the list if not already present
				// and if the poll is not expired
				participants.unshift(sessionStore.currentUser)
			} else if (currentUserIndex > 0) {
				// move current user to the begining of the list, if not already first
				const currentUser = participants.splice(currentUserIndex, 1)[0]
				participants.unshift(currentUser)
			}

			// sort participants by votes for the selected option if sortByOption is set
			// TODO: Future usage: This is only valid for simple votes (not ranked)
			if (state.sortByOption > 0 && pollStore.voteVariant === 'simple') {
				participants.sort((aUser, bUser) => {
					// find the votes for the selected option and the users to compare
					const aAnswer =
						answerSortOrder[
							state.votes.find(
								(vote) =>
									vote.user.id === aUser.id
									&& vote.optionId === state.sortByOption,
							)?.answer ?? ''
						]
					const bAnswer =
						answerSortOrder[
							state.votes.find(
								(vote) =>
									vote.user.id === bUser.id
									&& vote.optionId === state.sortByOption,
							)?.answer ?? ''
						]

					if (aAnswer < bAnswer) {
						return -1
					}

					if (aAnswer > bAnswer) {
						return 1
					}

					return 0
				})
			}

			return participants
		},

		/**
		 * Returns a sorted list of votes for all options and all users
		 * Missing votes are filled with default values to get a complete set
		 * Sort order is sortedParticipants and then options
		 * This is to get a distinc list of votes to fill the table grid
		 * @param state
		 * @return
		 */
		sortedVotes(state): Vote[] {
			const optionsStore = useOptionsStore()
			const virtualVotes: Vote[] = []
			this.sortedParticipants.forEach((user) => {
				optionsStore.options.forEach((option) => {
					const found = state.votes.find(
						(vote: Vote) =>
							vote.optionId === option.id && vote.user.id === user.id,
					)
					if (found) {
						virtualVotes.push(found)
					} else {
						virtualVotes.push({
							answer: '',
							optionText: option.text,
							user,
							answerSymbol: '',
							deleted: 0,
							id: 0,
							optionId: option.id,
							pollId: option.pollId,
						})
					}
				})
			})
			return virtualVotes
		},

		hasVotes: (state) => state.votes.length > 0,
	},

	actions: {
		addChunk(): void {
			this.meta.chunks.loaded = this.meta.chunks.loaded + 1
		},

		countAllVotesByAnswer(answer: Answer): number {
			return this.votes.filter((vote) => vote.answer === answer).length
		},

		getVote(payload: { user: User; option: Option }): Vote {
			const found = this.sortedVotes.find(
				(vote: Vote) =>
					vote.user.id === payload.user.id
					&& vote.optionText === payload.option.text,
			)
			if (found === undefined) {
				return {
					answer: '',
					optionText: payload.option.text,
					user: payload.user,
					answerSymbol: '',
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

				this.votes = response.data.votes
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.$reset()
				throw error
			}
		},

		setItem(payload: { option: Option; vote: Vote }) {
			const index = this.votes.findIndex(
				(vote: Vote) =>
					vote.pollId === payload.option.pollId
					&& vote.user.id === payload.vote.user.id
					&& vote.optionText === payload.option.text,
			)
			if (index > -1) {
				this.votes[index] = Object.assign(this.votes[index], payload.vote)
				return
			}
			this.votes.push(payload.vote)
		},

		async set(payload: { option: Option; setTo: Answer }) {
			// place a fake vote for a slightly better UX in huge polls
			this.setItem({
				option: payload.option,
				vote: {
					answer: payload.setTo,
					optionText: payload.option.text,
					user: useSessionStore().currentUser,
					answerSymbol: '',
					deleted: 0,
					id: 0,
					optionId: payload.option.id,
					pollId: payload.option.pollId,
				},
			})

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

				// in case of limited votes, reload the poll for current user status
				if (
					pollStore.configuration.maxVotesPerOption
					|| pollStore.configuration.maxVotesPerUser
				) {
					pollStore.load()
				}

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
