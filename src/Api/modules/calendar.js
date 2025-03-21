/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const calendar = {
	getCalendars() {
		return httpInstance.request({
			method: 'GET',
			url: 'calendars',
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getCalendars.name
				].handleRequestCancellation().token,
		})
	},
	getEvents(optionId) {
		return httpInstance.request({
			method: 'GET',
			url: `option/${optionId}/events`,
			params: {
				tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
				time: +new Date(),
			},
			cancelToken:
				cancelTokenHandlerObject[
					this.getEvents.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(calendar)

export default calendar
