/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const defaultComments = () => {
	return {
		list: [],
	}
}

const state = defaultComments()

const namespaced = true

const mutations = {

	set(state, payload) {
		state.list = payload.comments
	},

	reset(state) {
		Object.assign(state, defaultComments())
	},

	add(state, payload) {
		state.list.push(payload)
	},

	delete(state, payload) {
		state.list = state.list.filter(comment => {
			return comment.id !== payload.comment.id
		})
	},
}

const getters = {
	count: state => {
		return state.list.length
	},
}

const actions = {
	add(context, payload) {
		const endPoint = 'apps/polls/comment/add'
		return axios.post(generateUrl(endPoint), {
			message: payload.message,
			pollId: context.rootState.poll.id,
			token: context.rootState.poll.acl.token,
		})
			.then((response) => {
				context.commit('add', response.data)
				return response.data
			}, (error) => {
				console.error('Error writing comment', { error: error.response }, { payload: payload })
				throw error
			})
	},

	delete(context, payload) {
		const endPoint = 'apps/polls/comment/delete'
		context.commit('delete', { comment: payload.comment })
		return axios.post(generateUrl(endPoint), {
			token: context.rootState.poll.acl.token,
			commentId: payload.comment.id,
		})
			.then((response) => {
				context.commit('delete', { comment: response.data.comment })
				return response.data
			}, (error) => {
				console.error('Error deleting comment', { error: error.response }, { payload: payload })
				throw error
			})
	},
}

export default { namespaced, state, mutations, actions, getters }
