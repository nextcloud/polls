/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { CommentsAPI, PublicAPI } from '../../Api/index.js'
import { groupComments, Logger } from '../../helpers/index.js'

const defaultComments = () => ({
	list: [],
})

const namespaced = true
const state = defaultComments()

const mutations = {

	set(state, payload) {
		state.list = payload.comments
	},

	reset(state) {
		Object.assign(state, defaultComments())
	},

	add(state, payload) {
		state.list.push(payload.comment)
	},

	setItem(state, payload) {
		const index = state.list.findIndex((comment) =>
			parseInt(comment.id) === payload.comment.id,
		)

		if (index < 0) {
			state.list.push(payload.comment)
		} else {
			state.list[index] = Object.assign(state.list[index], payload.comment)
		}
	},

}

const getters = {
	count: (state) => state.list.length,
	groupedComments: (state) => groupComments(state.list),
}

const actions = {
	async list(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getComments(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await CommentsAPI.getComments(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}

			context.commit('set', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			context.commit('reset')
		}
	},

	async add(context, payload) {
		try {
			if (context.rootState.route.name === 'publicVote') {
				await PublicAPI.addComment(context.rootState.route.params.token, payload.message)
			} else if (context.rootState.route.name === 'vote') {
				await CommentsAPI.addComment(context.rootState.route.params.id, payload.message)
			} else {
				context.commit('reset')
				return
			}

			context.dispatch('list')
			// context.commit('add', { comment: response.data.comment })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing comment', { error, payload })
			throw error
		}
	},

	async delete(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.deleteComment(context.rootState.route.params.token, payload.comment.id)
			} else {
				response = await CommentsAPI.deleteComment(payload.comment.id)
			}

			context.commit('setItem', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting comment', { error, payload })
			throw error
		}
	},

	async restore(context, payload) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.restoreComment(context.rootState.route.params.token, payload.comment.id, { comment: payload.comment })
			} else {
				response = await CommentsAPI.restoreComment(payload.comment.id)
			}

			context.commit('setItem', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error restoring comment', { error, payload })
			throw error
		}
	},
}

export default { namespaced, state, mutations, actions, getters }
