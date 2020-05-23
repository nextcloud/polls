/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
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

const defaultSettings = () => {
	return {
		user: {
			experimental: false,
			useImage: false,
			imageUrl: '',
			glassyNavigation: false,
			glassySidebar: false,
		},
	}
}

const state = defaultSettings()

const mutations = {
	reset(state) {
		Object.assign(state, defaultSettings())
	},

	setUserSetting(state, payload) {
		Object.assign(state.user, payload)
	},
}

const getters = {
}

const actions = {
	getSettings(context) {
		const endPoint = 'apps/polls/preferences/get'

		return axios.get(generateUrl(endPoint))
			.then((response) => {
				context.commit('setUserSetting', JSON.parse(response.data.preferences))
			})
			.catch(() => {
				context.commit('reset')
			})
	},
	writeSetting(context) {
		const endPoint = 'apps/polls/preferences/write'
		// context.commit('setUserSetting', { settings: payload })

		return axios.post(generateUrl(endPoint), { settings: context.state.user })
			.then((response) => {
				context.commit('setUserSetting', JSON.parse(response.data.preferences))
			})
			.catch((error) => {
				console.error('Error writing preferences', { error: error.response }, { preferences: state.user })
				throw error
			})
	},

}

export default { state, mutations, getters, actions }
