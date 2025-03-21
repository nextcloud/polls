/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const votes = {
	getVotes(pollId) {
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

	setVote(optionId, setTo) {
		return httpInstance.request({
			method: 'PUT',
			url: 'vote',
			data: { optionId, setTo },
			cancelToken:
				cancelTokenHandlerObject[
					this.setVote.name
				].handleRequestCancellation().token,
		})
	},

	removeUser(pollId, userId = null) {
		return httpInstance.request({
			method: 'DELETE',
			url: userId ? `poll/${pollId}/user/${userId}` : `poll/${pollId}/user`,
			cancelToken:
				cancelTokenHandlerObject[
					this.removeUser.name
				].handleRequestCancellation().token,
		})
	},

	removeOrphanedVotes(pollId) {
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
