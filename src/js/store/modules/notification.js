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

const defaultNotification = () => {
	return {
		subscribed: false
	}
}

const state = defaultNotification()

const mutations = {

	setNotification(state, payload) {
		state.subscribed = payload
	}

}

const actions = {
	getSubscription({ commit }, payload) {
		axios.get(OC.generateUrl('apps/polls/get/notification/' + payload.pollId))
			.then((response) => {
				commit('setNotification', true)
			})
			.catch(() => {
				commit('setNotification', false)
			})
	},

	writeSubscriptionPromise({ commit }, payload) {
		return axios.post(OC.generateUrl('apps/polls/set/notification'), { pollId: payload.pollId, subscribed: state.subscribed })
			.then((response) => {
				console.error(response.data)
			}, (error) => {
				console.error(error.response)
			})
	}
}

export default { state, mutations, actions }
