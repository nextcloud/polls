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

import { getCurrentUser } from '@nextcloud/auth'
import { PollsAPI } from '../../Api/polls.js'

const namespaced = true
const state = {
	list: [],
}

const mutations = {
	set(state, payload) {
		Object.assign(state, payload)
	},
}

const getters = {
	filtered: (state) => (filterId) => state.list,
}

const actions = {
	async list(context) {
		if (!getCurrentUser().isAdmin) {
			return
		}

		try {
			const response = await PollsAPI.getPollsForAdmin()
			context.commit('set', { list: response.data })
		} catch (e) {
			console.error('Error loading polls', { error: e.response })
		}
	},

	takeOver(context, payload) {
		if (!getCurrentUser().isAdmin) {
			return
		}

		PollsAPI.takeOver(payload.pollId)
	},
}

export default { namespaced, state, mutations, getters, actions }
