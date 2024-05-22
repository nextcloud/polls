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
