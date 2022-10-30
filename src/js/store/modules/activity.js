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
import { generateOcsUrl } from '@nextcloud/router'
import axiosDefaultConfig from '../../helpers/AxiosDefault.js'

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
		const params = new URLSearchParams()
		params.append('format', 'json')
		params.append('since', 0)
		params.append('limit', 50)
		params.append('object_type', 'poll')
		params.append('object_id', context.rootState.route.params.id)
		const endPoint = generateOcsUrl('apps/activity/api/v2/activity/filter?') + params

		try {
			const response = await axios.get(endPoint, axiosDefaultConfig)
			context.commit('set', response.data.ocs.data)
		} catch {
			context.commit('reset')
		}
	},

}

export default { namespaced, state, mutations, actions }
