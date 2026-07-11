/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, ocsInstance } from './HttpApi.js'

// eslint-disable-next-line prefer-const -- assigned below, after `activity` is fully defined
let cancelTokenHandlerObject: ReturnType<typeof createCancelTokenHandler>

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
			signal: cancelTokenHandlerObject[
				this.getActivities.name
			].handleRequestCancellation().signal,
		})
		return response
	},
}

cancelTokenHandlerObject = createCancelTokenHandler(activity)

export default activity
