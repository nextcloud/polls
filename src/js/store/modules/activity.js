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

import { ActivityAPI } from '../../Api/activity.js'

const defaultActivities = () => ({
	list: [],
})

const namespaced = true
const state = defaultActivities()

const mutations = {
	set(state, payload) {
		state.list = payload
	},

	reset(state) {
		Object.assign(state, defaultActivities())
	},

	deleteActivities(state, payload) {
		state.list = state.list.filter((vote) => vote.user.userId !== payload.userId)
	},

}

const actions = {
	async list(context) {

		try {
			const response = await ActivityAPI.getActivities(context.rootState.route.params.id)
			context.commit('set', response.data.ocs.data)
		} catch (error) {
			context.commit('reset')
		}
	},

}

export default { namespaced, state, mutations, actions }
