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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const defaultSubscription = () => ({
	subscribed: false,
})

const namespaced = true
const state = defaultSubscription()
const axiosDefaultConfig = { headers: { Accept: 'application/json' } }

const mutations = {

	set(state, payload) {
		state.subscribed = payload.subscribed
	},

	reset(state) {
		Object.assign(state, defaultSubscription())
	},

}

const actions = {

	async get(context) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = `${endPoint}/s/${context.rootState.route.params.token}/subscription`
		} else if (context.rootState.route.name === 'vote') {
			endPoint = `${endPoint}/poll/${context.rootState.route.params.id}/subscription`
		} else {
			context.commit('reset')
			return
		}

		try {
			const response = await axios.get(generateUrl(endPoint), {
				...axiosDefaultConfig,
				params: { time: +new Date() },
			})
			context.commit('set', response.data)
		} catch {
			context.commit('set', false)
		}
	},

	async update(context, payload) {
		let endPoint = 'apps/polls'

		if (context.rootState.route.name === 'publicVote') {
			endPoint = `${endPoint}/s/${context.rootState.route.params.token}${payload ? '/subscribe' : '/unsubscribe'}`
		} else if (context.rootState.route.name === 'vote') {
			endPoint = `${endPoint}/poll/${context.rootState.route.params.id}${payload ? '/subscribe' : '/unsubscribe'}`
		} else {
			context.commit('reset')
			return
		}

		try {
			const response = await axios.put(generateUrl(endPoint), null, axiosDefaultConfig)
			context.commit('set', response.data)
		} catch (e) {
			console.error(e.response)
		}
	},
}

export default { namespaced, state, mutations, actions }
