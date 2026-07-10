import type { AxiosResponse } from '@nextcloud/axios'
import type { Answer, Vote } from '../../stores/votes.types'
import type { RemoveVotesResponse, setVoteResponse } from './api.types'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi'

const votes = {
	getVotes(pollId: number): Promise<AxiosResponse<{ votes: Vote[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/votes`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getVotes.name
			].handleRequestCancellation().signal,
		})
	},

	setVote(
		optionId: number,
		setTo: Answer,
	): Promise<AxiosResponse<setVoteResponse>> {
		return httpInstance.request({
			method: 'PUT',
			url: 'vote',
			data: {
				optionId,
				setTo,
			},
			signal: cancelTokenHandlerObject[
				this.setVote.name
			].handleRequestCancellation(optionId).signal,
		})
	},

	resetVotes(
		pollId: number,
		userId: string | null = null,
	): Promise<AxiosResponse<RemoveVotesResponse>> {
		return httpInstance.request({
			method: 'DELETE',
			url: userId ? `poll/${pollId}/user/${userId}` : `poll/${pollId}/user`,
			signal: cancelTokenHandlerObject[
				this.resetVotes.name
			].handleRequestCancellation().signal,
		})
	},

	removeOrphanedVotes(
		pollId: number,
	): Promise<AxiosResponse<RemoveVotesResponse>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}/votes/orphaned`,
			signal: cancelTokenHandlerObject[
				this.removeOrphanedVotes.name
			].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(votes)

export default votes
