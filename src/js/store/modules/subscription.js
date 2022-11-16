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

import { PollsAPI } from '../../Api/polls.js'
import { PublicAPI } from '../../Api/public.js'

const defaultSubscription = () => ({
	subscribed: false,
})

const namespaced = true
const state = defaultSubscription()

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
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getSubscription(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await PollsAPI.getSubscription(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}
			context.commit('set', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			context.commit('set', false)
			throw e
		}
	},

	async update(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.setSubscription(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await PollsAPI.setSubscription(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}
			context.commit('set', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error(e.response)
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions }
