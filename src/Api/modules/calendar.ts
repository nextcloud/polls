/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { AxiosResponse } from '@nextcloud/axios'
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'
import { Calendar } from '../../stores/preferences.js'
import { CalendarEvent } from '../../components/Calendar/CalendarPeek.vue'

const calendar = {
	getCalendars(): Promise<AxiosResponse<{ calendars: Calendar[] }>> {
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
	getEvents(
		optionId: number,
	): Promise<AxiosResponse<{ events: CalendarEvent[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `option/${optionId}/events`,
			params: {
				tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
				time: +new Date(),
			},
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(calendar)

export default calendar
