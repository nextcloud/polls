/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { AxiosResponse } from '@nextcloud/axios'
import { Answer, Vote } from '../../stores/votes.js'
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'
import { RemoveVotesResponse, setVoteResponse } from './api.types.js'

const votes = {
	getVotes(pollId: number): Promise<AxiosResponse<{ votes: Vote[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/votes`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getVotes.name
				].handleRequestCancellation().token,
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
			cancelToken:
				cancelTokenHandlerObject[
					this.setVote.name
				].handleRequestCancellation().token,
		})
	},

	resetVotes(
		pollId: number,
		userId: string | null = null,
	): Promise<AxiosResponse<RemoveVotesResponse>> {
		return httpInstance.request({
			method: 'DELETE',
			url: userId ? `poll/${pollId}/user/${userId}` : `poll/${pollId}/user`,
			cancelToken:
				cancelTokenHandlerObject[
					this.resetVotes.name
				].handleRequestCancellation().token,
		})
	},

	removeOrphanedVotes(
		pollId: number,
	): Promise<AxiosResponse<RemoveVotesResponse>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}/votes/orphaned`,
			cancelToken:
				cancelTokenHandlerObject[
					this.removeOrphanedVotes.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(votes)

export default votes
