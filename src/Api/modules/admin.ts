/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { httpInstance, createCancelTokenHandler } from './HttpApi'

const adminJobs = {
	runAutoReminder() {
		return httpInstance.request({
			method: 'GET',
			url: 'administration/autoreminder/run',
			cancelToken:
				cancelTokenHandlerObject[
					this.runAutoReminder.name
				].handleRequestCancellation().token,
		})
	},
	runJanitor() {
		return httpInstance.request({
			method: 'GET',
			url: 'administration/janitor/run',
			cancelToken:
				cancelTokenHandlerObject[
					this.runJanitor.name
				].handleRequestCancellation().token,
		})
	},
	runNotification() {
		return httpInstance.request({
			method: 'GET',
			url: 'administration/notification/run',
			cancelToken:
				cancelTokenHandlerObject[
					this.runNotification.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(adminJobs)

export default adminJobs
