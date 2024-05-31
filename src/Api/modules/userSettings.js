/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const userSettings = {
	getUserSettings() {
		return httpInstance.request({
			method: 'GET',
			url: 'preferences',
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getUserSettings.name].handleRequestCancellation().token,
		})
	},

	getAcl() {
		return httpInstance.request({
			method: 'GET',
			url: 'acl',
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getAcl.name].handleRequestCancellation().token,
		})
	},

	writeUserSettings(preferences) {
		return httpInstance.request({
			method: 'POST',
			url: 'preferences',
			data: { preferences },
			cancelToken: cancelTokenHandlerObject[this.writeUserSettings.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(userSettings)

export default userSettings
