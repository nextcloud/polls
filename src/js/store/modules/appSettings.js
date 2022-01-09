/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2021 Rene Gieling <github@dartcafe.de>
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

const defaultAppSettings = () => ({
	allowPublicShares: true,
	allowAllAccess: true,
	allowPollCreation: true,
	allowPollDownload: true,
	showLogin: true,
	allAccessGroups: [],
	publicSharesGroups: [],
	pollCreationGroups: [],
	pollDownloadGroups: [],
	autoArchive: false,
	autoArchiveOffset: 30,
	updateType: 'longPolling',
	useActivity: false,
})

const state = defaultAppSettings()
const namespaced = true

const mutations = {
	reset(state) {
		Object.assign(state, defaultAppSettings())
	},

	set(state, payload) {
		Object.keys(payload).filter((key) => key in state).forEach((key) => {
			state[key] = payload[key]
		})
	},
}

const actions = {
	async get(context) {
		const endPoint = 'apps/polls/settings/app'
		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('set', response.data.appSettings)
		} catch {
			context.commit('reset')
		}
	},

	async write(context) {
		const endPoint = 'apps/polls/settings/app'
		try {
			const response = await axios.post(generateUrl(endPoint), { appSettings: context.state })
			context.commit('set', response.data.appSettings)
		} catch (e) {
			console.error('Error writing appSettings', { error: e.response }, { appSettings: state })
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions }
