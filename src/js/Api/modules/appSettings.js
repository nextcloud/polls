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

const appSettings = {
	getAppSettings() {
		return httpInstance.request({
			method: 'GET',
			url: 'settings/app',
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getAppSettings.name].handleRequestCancellation().token,
		})
	},

	writeAppSettings(appSettings) {
		return httpInstance.request({
			method: 'POST',
			url: 'settings/app',
			data: { appSettings },
			cancelToken: cancelTokenHandlerObject[this.writeAppSettings.name].handleRequestCancellation().token,
		})
	},

	getGroups(query) {
		return httpInstance.request({
			method: 'GET',
			url: `groups${query.trim() ? `/${query.trim()}` : ''}`,
			cancelToken: cancelTokenHandlerObject[this.writeAppSettings.name].handleRequestCancellation().token,
		})
	},

	getUsers(query) {
		return httpInstance.request({
			method: 'GET',
			url: `search/users${query.trim() ? `/${query.trim()}` : ''}`,
			cancelToken: cancelTokenHandlerObject[this.writeAppSettings.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(appSettings)

export default appSettings
