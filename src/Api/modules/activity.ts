/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { ocsInstance, createCancelTokenHandler } from './HttpApi'

const activity = {
	getActivities(pollId: number) {
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
			cancelToken:
				cancelTokenHandlerObject[
					this.getActivities.name
				].handleRequestCancellation().token,
		})
		return response
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(activity)

export default activity
