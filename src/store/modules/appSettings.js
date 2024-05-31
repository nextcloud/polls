/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { AppSettingsAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultAppSettings = () => ({
	allAccessGroups: [],
	allowCombo: true,
	allowPublicShares: true,
	allowAllAccess: true,
	allowPollCreation: true,
	allowPollDownload: true,
	autoArchive: false,
	autoArchiveOffset: 30,
	defaultPrivacyUrl: '',
	defaultImprintUrl: '',
	disclaimer: '',
	imprintUrl: '',
	legalTermsInEmail: false,
	privacyUrl: '',
	showMailAddresses: false,
	showLogin: true,
	updateType: 'noPolling',
	useActivity: false,
	useCollaboration: true,
	navigationPollsInList: true,
	usePrivacyUrl: '',
	useImprintUrl: '',
	comboGroups: [],
	publicSharesGroups: [],
	pollCreationGroups: [],
	pollDownloadGroups: [],
	showMailAddressesGroups: [],
})

const namespaced = true
const state = defaultAppSettings()

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
		try {
			const response = await AppSettingsAPI.getAppSettings()
			context.commit('set', response.data.appSettings)
		} catch (error) {
			Logger.debug('Error getting appSettings', { error })
		}
	},

	async write(context) {
		try {
			const response = await AppSettingsAPI.writeAppSettings(context.state)
			context.commit('set', response.data.appSettings)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing appSettings', { error, appSettings: state })
			throw error
		}
	},
}

export default { namespaced, state, mutations, actions }
