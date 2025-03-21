/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { VotesAPI, OptionsAPI, PollsAPI } from '../Api/index.js'
import { Participant } from '../Types/index.ts'
import { Logger, uniqueOptions, uniqueParticipants } from '../helpers/index.ts'
import { Option } from './options.ts'
import { Poll } from './poll.ts'
import { Vote } from './votes.ts'
import { sortBy } from 'lodash'
import { usePreferencesStore } from './preferences.ts'
import { usePollsStore } from './polls.ts'

export type Combo = {
	id: number
	options: Option[],
	polls: Poll[],
	participants: Participant[],
	votes: Vote[],
}

export const useComboStore = defineStore('combo', {
	state: (): Combo => ({
		id: 1,
		options: [],
		polls: [],
		participants: [],
		votes: [],
	}),

	getters: {
		poll: (state) => (pollId: number) => state.polls.find((poll: Poll) => poll.id === pollId),
		votesInPoll: (state) => (pollId: number) => state.votes.filter((vote: Vote) => vote.pollId === pollId),
		participantsInPoll: (state) => (pollId: number) => state.participants.filter((participant: Participant) => participant.pollId === pollId),
		pollIsListed: (state) => (pollId: number) => !!state.polls.find((poll: Poll) => poll.id === pollId),
		pollCombo: (state) => state.polls.map((poll: Poll) => poll.id),
		optionBelongsToPoll: (state) => (payload: { text: string; pollId: number }) => !!state.options.find((option) => option.text === payload.text && option.pollId === payload.pollId),
		uniqueOptions: (state) => sortBy(uniqueOptions(state.options), 'timestamp'),

		getVote: (state) => (payload: { userId: string; optionText: string, pollId: number }) => {
			const found = state.votes.find((vote: Vote) => (
				vote.user.id === payload.userId
				&& vote.optionText === payload.optionText
				&& vote.pollId === payload.pollId))
			if (found === undefined) {
				return {
					answer: '',
					optionText: payload.optionText,
					userId: payload.userId,
				}
			}
			return found
		},
	},

	actions: {
		async add(pollId: number) {
			return Promise.all([
				this.addPoll({ pollId }),
				this.addVotes({ pollId }),
				this.addOptions({ pollId }),
			])
		},

		async remove(pollId: number) {
			return Promise.all([
				this.removePoll({ pollId }),
				this.removeVotes({ pollId }),
				this.removeOptions({ pollId }),
			])
		},

		removePoll(payload: { pollId: number }) {
			this.polls = this.polls.filter((poll: Poll) => poll.id !== payload.pollId)
		},

		removeVotes(payload: { pollId: number }) {
			this.votes = this.votes.filter((vote: Vote) => vote.pollId !== payload.pollId)
			this.participants = uniqueParticipants(this.votes)
		},

		removeOptions(payload: { pollId: number }) {
			this.options = this.options.filter((option: Option) => option.pollId !== payload.pollId)
		},

		async verifyPollsFromSettings() {
			const preferencesStore = usePreferencesStore()
			preferencesStore.user.pollCombo.forEach(pollId => {
				if (!this.pollCombo.includes(pollId)) {
					this.add(pollId)
				}
			})
		},

		async cleanUp() {
			const pollsStore = usePollsStore()
			this.polls.forEach((comboPoll: Poll) => {
				if (pollsStore.list.findIndex((poll) => poll.id === comboPoll.id && !poll.status.isDeleted) < 0) {
					this.removePoll({ pollId: comboPoll.id })
				}
			})
		},

		async togglePollItem(pollId: number) {
			if (this.pollIsListed(pollId)) {
				this.remove(pollId)
			} else {
				this.add(pollId)
			}
		},

		async addPoll(payload: { pollId: number }): Promise<void> {
			try {
				const response = await PollsAPI.getPoll(payload.pollId)
				this.polls.push(response.data.poll)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading poll for combo', { error })
			}
		},

		async addOptions(payload: { pollId: number }): Promise<void> {
			try {
				const response = await OptionsAPI.getOptions(payload.pollId)
				this.options.push(...response.data.options)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading options for combo', { error })
			}
		},

		async addVotes(payload: { pollId: number }): Promise<void> {
			try {
				const response = await VotesAPI.getVotes(payload.pollId)
				this.votes.push(...response.data.votes)
				this.participants = uniqueParticipants(this.votes)

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading options for combo', { error })
			}
		},
	},
})
