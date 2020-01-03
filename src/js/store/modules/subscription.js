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

const defaultSubscription = () => {
	return {
		subscribed: false
	}
}

const state = defaultSubscription()

const mutations = {

	setSubscription(state, payload) {
		state.subscribed = payload
	}

}

const actions = {
	getSubscription(context, payload) {
		axios.get(OC.generateUrl('apps/polls/subscription/get/' + payload.pollId))
			.then(() => {
				context.commit('setSubscription', true)
			})
			.catch(() => {
				context.commit('setSubscription', false)
			})
	},

	writeSubscriptionPromise(context, payload) {
		return axios.post(OC.generateUrl('apps/polls/subscription/set/'), { pollId: payload.pollId, subscribed: state.subscribed })
			.then(() => {
			}, (error) => {
				console.error(error.response)
			})
	}
}

export default { state, mutations, actions }
