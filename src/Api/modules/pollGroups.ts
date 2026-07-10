import type { AxiosResponse } from '@nextcloud/axios'
import type { Poll } from '../../stores/poll.types'
import type { PollGroup } from '../../stores/pollGroups.types'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi'

const pollGroups = {
	getPollGroups(): Promise<AxiosResponse<{ pollGroups: PollGroup[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: 'pollgroups',
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getPollGroups.name
			].handleRequestCancellation().signal,
		})
	},

	addPollToGroup(
		pollId: number,
		pollGroupId?: number,
		pollGroupName?: string,
	): Promise<AxiosResponse<{ pollGroup: PollGroup; poll: Poll }>> {
		let url = ''
		let verb = 'PUT'
		let data = {}

		if (pollGroupId) {
			url = `pollgroup/${pollGroupId}/poll/${pollId}`
		} else if (pollGroupName) {
			verb = 'POST'
			url = `pollgroup/new/poll/${pollId}`
			data = { pollGroupName }
		} else {
			throw new Error(
				'You must provide either a pollGroupId or a pollGroupName',
			)
		}

		return httpInstance.request({
			method: verb,
			url,
			data,
			signal: cancelTokenHandlerObject[
				this.addPollToGroup.name
			].handleRequestCancellation().signal,
		})
	},

	removePollFromGroup(
		pollGroupId: number,
		pollId: number,
	): Promise<AxiosResponse<{ pollGroup: PollGroup | null; poll: Poll }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `pollgroup/${pollGroupId}/poll/${pollId}`,
			signal: cancelTokenHandlerObject[
				this.removePollFromGroup.name
			].handleRequestCancellation().signal,
		})
	},

	updatePollGroup(payload: {
		id: number
		name: string
		titleExt: string
		description: string
	}): Promise<AxiosResponse<{ pollGroup: PollGroup }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `pollgroup/${payload.id}/update`,
			data: {
				name: payload.name,
				titleExt: payload.titleExt,
				description: payload.description,
			},
			signal: cancelTokenHandlerObject[
				this.updatePollGroup.name
			].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(pollGroups)

export default pollGroups
