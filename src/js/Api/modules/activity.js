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

import { ocsInstance, createCancelTokenHandler } from './HttpApi.js'

const activity = {
	getActivities(pollId) {
		const response = ocsInstance.request({
			method: 'GET',
			url: 'activity/api/v2/activity/polls',
			params: {
				format: 'json',
				since: 0,
				limit: 50,
				object_type: 'poll',
				object_id: pollId,
			},
			cancelToken: cancelTokenHandlerObject[this.getActivities.name].handleRequestCancellation().token,
		})
		return response
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(activity)

export default activity
