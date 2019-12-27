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
import sortBy from 'lodash/sortBy'

const defaultComments = () => {
	return {
		list: []
	}
}

const state = defaultComments()

const mutations = {

	setComments(state, payload) {
		Object.assign(state, payload)
	},

	reset(state) {
		Object.assign(state, defaultComments())
	},

	addComment(state, payload) {
		state.list.push(payload)
	}

}

const getters = {
	sortedComments: state => {
		return sortBy(state.list, 'date').reverse()
	},

	countComments: state => {
		return state.list.length
	}
}

const actions = {

	loadPoll({ commit, rootState }, payload) {
		commit('reset')
		let endPoint = 'apps/polls/comments/get/'

		if (payload.token !== undefined) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			return
		}

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				commit('setComments', { 'list': response.data })
			}, (error) => {
				console.error('Error loading comments', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	},

	setCommentAsync({ commit, rootState }, payload) {
		let endPoint = 'apps/polls/comment/write/'

		if (rootState.acl.foundByToken) {
			endPoint = endPoint.concat('s/')
		}

		return axios.post(OC.generateUrl(endPoint), {
			pollId: rootState.poll.id,
			token: rootState.acl.token,
			message: payload.message,
			userId: rootState.acl.userId
		})
			.then((response) => {
				commit('addComment', response.data)
				return response.data
			}, (error) => {
				console.error('Error writing comment', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	}
}

export default { state, mutations, actions, getters }
