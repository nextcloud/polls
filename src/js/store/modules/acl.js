/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		shareCreate: true,
		shareCreateExternal: true,
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
			context.commit('set', { acl: response.data.acl })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return

			context.commit('reset')
			if (context.rootState.route.name === null) {
				// TODO: for some reason unauthorized users first get the root route resulting in a 401
				// and after that the publicVote route is called as next route
				// therefore we just debug the error and reset the acl

				Logger.debug('getAcl failed', error)
				context.commit('reset')
			} else {
				throw error
			}
		}
	},
}
export default { namespaced, state, mutations, actions }
