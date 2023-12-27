/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { CommentsAPI, PublicAPI } from '../../Api/index.js'
import { groupComments } from '../../helpers/index.js'

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

	setDeleted(state, payload) {
		const index = state.list.findIndex((comment) =>
			parseInt(comment.id) === payload.comment.id,
		)

		if (index > -1) {
			state.list[index].deleted = payload.comment.deleted
			return
		}
		state.list.push(payload.comment)
	},

	setItem(state, payload) {
		const index = state.list.findIndex((comment) =>
			parseInt(comment.id) === payload.comment.id,
		)

		if (index > -1) {
			state.list[index] = Object.assign(state.list[index], payload.comment)
			return
		}
		state.list.push(payload.commet)
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
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
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
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error writing comment', { error: e.response }, { payload })
			throw e
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

			context.commit('setDeleted', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error deleting comment', { error: e.response }, { payload })
			throw e
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

			context.commit('setDeleted', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error restoring comment', { error: e.response }, { payload })
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions, getters }
