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

import axios from 'nextcloud-axios'
import sortBy from 'lodash/sortBy'

const defaultComments = () => {
	return {
		list: []
	}
}

const state = defaultComments()

const mutations = {

	commentsSet(state, payload) {
		Object.assign(state, payload)
	},

	commentsReset(state) {
		Object.assign(state, defaultComments())
	},

	commentAdd(state, payload) {
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
	loadComments({ commit, rootState }, payload) {
		commit({ type: 'commentsReset' })
		axios
			.get(OC.generateUrl('apps/polls/get/comments/' + payload.pollId))
			.then((response) => {
				commit('commentsSet', {
					'list': response.data
				})
			}, (error) => {
				commit({ type: 'commentsReset' })
				/* eslint-disable-next-line no-console */
				console.log(error)
			})
	},

	writeCommentPromise({ commit, rootState }, payload) {
		return axios
			.post(OC.generateUrl('apps/polls/write/comment'), { pollId: rootState.event.id, message: payload })
			.then((response) => {
				commit('commentAdd', response.data)
			}, (error) => {
				/* eslint-disable-next-line no-console */
				console.log('writeCommentPromise error', error)
			})
	}
}

export default { state, mutations, actions, getters }
