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

import { CommentsAPI } from '../../Api/comments.js'
import { PublicAPI } from '../../Api/public.js'

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

	delete(state, payload) {
		state.list = state.list.filter((comment) => comment.id !== payload.comment.id)
	},
}

const getters = {
	count: (state) => state.list.length,
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
		} catch {
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
			console.error('Error writing comment', { error: e.response }, { payload })
			throw e
		}
	},

	async delete(context, payload) {
		try {
			if (context.rootState.route.name === 'publicVote') {
				await PublicAPI.deleteComment(context.rootState.route.params.token, payload.comment.id)
			} else {
				await CommentsAPI.deleteComment(payload.comment.id)
			}

			context.commit('delete', { comment: payload.comment })
		} catch (e) {
			console.error('Error deleting comment', { error: e.response }, { payload })
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions, getters }
