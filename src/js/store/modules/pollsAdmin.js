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
import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'

const state = {
	list: [],
}

const namespaced = true

const mutations = {
	set(state, payload) {
		Object.assign(state, payload)
	},
}

const getters = {
	filtered: (state) => (filterId) => {
		return state.list
	},
}

const actions = {
	async load(context) {
		const endPoint = 'apps/polls/administration'
		if (getCurrentUser().isAdmin) {
			try {
				const response = await axios.get(generateUrl(endPoint + '/polls'))
				context.commit('set', { list: response.data })
			} catch (e) {
				console.error('Error loading polls', { error: e.response })
			}
		}
	},

	takeOver(context, payload) {
		const endPoint = 'apps/polls/administration'
		if (getCurrentUser().isAdmin) {
			axios.get(generateUrl(endPoint + '/poll/' + payload.pollId + '/takeover'))
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
