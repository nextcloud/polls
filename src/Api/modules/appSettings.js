/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const appSettings = {
	getAppSettings() {
		return httpInstance.request({
			method: 'GET',
			url: 'settings/app',
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getAppSettings.name
				].handleRequestCancellation().token,
		})
	},

	writeAppSettings(appSettings) {
		return httpInstance.request({
			method: 'POST',
			url: 'settings/app',
			data: { appSettings },
			cancelToken:
				cancelTokenHandlerObject[
					this.writeAppSettings.name
				].handleRequestCancellation().token,
		})
	},

	getGroups(query) {
		return httpInstance.request({
			method: 'GET',
			url: `groups${query.trim() ? `/${query.trim()}` : ''}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.writeAppSettings.name
				].handleRequestCancellation().token,
		})
	},

	getUsers(query) {
		return httpInstance.request({
			method: 'GET',
			url: `search/users${query.trim() ? `/${query.trim()}` : ''}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.writeAppSettings.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(appSettings)

export default appSettings
