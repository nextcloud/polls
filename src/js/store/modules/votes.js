/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { PublicAPI, VotesAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultVotes = () => ({
	list: [],
})

const namespaced = true
const state = defaultVotes()

const mutations = {
	set(state, payload) {
		state.list = payload
	},

	reset(state) {
		Object.assign(state, defaultVotes())
	},

	deleteVotes(state, payload) {
		state.list = state.list.filter((vote) => vote.user.userId !== payload.userId)
	},

	setItem(state, payload) {
		const index = state.list.findIndex((vote) =>
			parseInt(vote.pollId) === payload.pollId
			&& vote.user.userId === payload.vote.user.userId
			&& vote.optionText === payload.option.text)
		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.vote)
			return
		}
		const vote = {... payload.vote}
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
		state.list.push(payload.vote)
	},
}

const getters = {
	countAllVotesByAnswer: (state) => (answer) => state.list.filter((vote) => vote.answer === answer).length,
	hasVotes: (state) => state.list.length > 0,

	getVote: (state) => (payload) => {
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
}

const actions = {
	async list(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getVotes(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await VotesAPI.getVotes(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}

			const votes = response.data.votes
			context.commit('set', votes)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			context.commit('reset')
			throw error
		}
	},

	async set(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.setVote(context.rootState.route.params.token, payload.option.id, payload.setTo)
			} else {
				response = await VotesAPI.setVote(payload.option.id, payload.setTo)
			}
			context.commit('setItem', { option: payload.option, pollId: context.rootState.poll.id, vote: response.data.vote })
			context.commit('options/set', { options: response.data.options }, { root: true })
			context.commit('poll/set', { poll: response.data.poll }, { root: true })
			// context.dispatch('options/list', null, { root: true })
			// context.dispatch('poll/get', null, { root: true })
			return response
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			if (error.response.status === 409) {
				context.dispatch('list')
				context.dispatch('options/list', null, { root: true })
				context.dispatch('poll/get', null, { root: true })
				throw error
			} else {
				Logger.error('Error setting vote', { error, payload })
				throw error
			}
		}
	},

	async resetVotes(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.removeVotes(context.rootState.route.params.token)
			} else {
				response = await VotesAPI.removeUser(context.rootState.route.params.id)
			}
			context.commit('deleteVotes', { userId: response.data.deleted })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting votes', { error })
			throw error
		}
	},

	async deleteUser(context, payload) {
		try {
			await VotesAPI.removeUser(context.rootState.route.params.id, payload.userId)
			context.commit('deleteVotes', payload)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting votes', { error, payload })
			throw error
		}
	},
	async removeOrphanedVotes(context, payload) {
		try {
			if (context.rootState.route.name === 'publicVote') {
				await PublicAPI.removeOrphanedVotes(context.rootState.route.params.token)
			} else {
				await VotesAPI.removeOrphanedVotes(context.rootState.route.params.id)
			}
			context.dispatch('poll/get', null, { root: true })
			context.dispatch('options/list', null, { root: true })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting orphaned votes', { error, payload })
			throw error
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
