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

const state = {
	list: []
}

const mutations = {
	setPolls(state, { list }) {
		state.list = list
	}
}

const getters = {
	countPolls: (state) => {
		return state.list.length
	},
	allPolls: (state) => {
		return state.list.filter(poll => (!poll.deleted))
	},
	myPolls: (state) => {
		return state.list.filter(poll => (poll.owner === OC.getCurrentUser().uid))
	},
	publicPolls: (state) => {
		return state.list.filter(poll => (poll.access === 'public'))
	},
	hiddenPolls: (state) => {
		return state.list.filter(poll => (poll.access === 'hidden'))
	},
	deletedPolls: (state) => {
		return state.list.filter(poll => (poll.deleted))
	}
}

const actions = {
	loadPolls({ commit }) {
		let endPoint = 'apps/polls/events/get/'

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				commit('setPolls', { list: response.data })
			}, (error) => {
				console.error(error.response)
			})
	},

	deletePollPromise(context, payload) {
		let endPoint = 'apps/polls/remove/poll'

		return axios.post(
			OC.generateUrl(endPoint),
			payload.event
		)
	}
}

export default { state, mutations, getters, actions }
