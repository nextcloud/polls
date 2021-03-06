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

const defaultSubscription = () => {
	return {
		subscribed: false,
	}
}

const state = defaultSubscription()

const namespaced = true

const mutations = {

	set(state, payload) {
		state.subscribed = payload.subscribed
	},

}

const actions = {

	async get(context) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else if (context.rootState.route.name === 'vote') {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		} else {
			return
		}
		try {
			const response = await axios.get(generateUrl(endPoint + '/subscription'))
			context.commit('set', response.data)
		} catch {
			context.commit('set', false)
		}
	},

	async update(context, payload) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else if (context.rootState.route.name === 'vote') {
			endPoint = endPoint + '/poll/' + context.rootState.route.params.id
		}

		try {
			const response = await axios.put(generateUrl(endPoint + (payload ? '/subscribe' : '/unsubscribe')))
			context.commit('set', response.data)
		} catch (e) {
			console.error(e.response)
		}
	},
}

export default { namespaced, state, mutations, actions }
