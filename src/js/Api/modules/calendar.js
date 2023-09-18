/**
 * @copyright Copyright (c) 2022 Rene Gieling <github@dartcafe.de>
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

import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const calendar = {
	getCalendars() {
		return httpInstance.request({
			method: 'GET',
			url: 'calendars',
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getCalendars.name].handleRequestCancellation().token,
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
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(calendar)

export default calendar
