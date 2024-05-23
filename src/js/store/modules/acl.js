/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2020 Rene Gieling <github@dartcafe.de>
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

import { UserSettingsAPI, PublicAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultAcl = () => ({
	token: '',
	currentUser: {
		userId: '',
		displayName: '',
		emailAddress: '',
		subName: '',
		subtitle: '',
		isNoUser: true,
		desc: '',
		type: 'user',
		id: '',
		user: '',
		organisation: '', 
		languageCode: '',
		localeCode: '',
		timeZone: '',
		icon: 'icon-user',
		categories: []
  	},
	appPermissions: {
		allAccess: false,
		publicShares: false,
		pollCreation: false,
		seeMailAddresses: false,
		pollDownload: false,
	},
	appSettings: {
		usePrivacyUrl: '',
		useImprintUrl: '',
		useLogin: false,
		useActivity: false,
		navigationPollsInList: false,
		updateType: 'noPolling',
	}
})

const namespaced = true
const state = defaultAcl()

const mutations = {

	set(state, payload) {
		Object.assign(state, payload.acl)
	},

	reset(state) {
		Object.assign(state, defaultAcl())
	},

}
const actions = {
	async get(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getAcl(context.rootState.route.params.token)
			} else {
				response = await UserSettingsAPI.getAcl()
			}
			// context.commit('reset')
			context.commit('set', { acl: response.data.acl })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return

			context.commit('reset')
			if (context.rootState.route.name === null) {
				// TODO: for some reason unauthorized users first get the root route resulting in a 401 
				// and after that the publicVote route is called as next route
				// therefore we just debug the error and reset the acl
				Logger.debug('getAcl failed', e)
			} else {
				throw e
			}
		}
	},
}
export default { namespaced, state, mutations, actions }
