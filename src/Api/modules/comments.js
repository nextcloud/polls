/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const comments = {
	getComments(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/comments`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getComments.name
				].handleRequestCancellation().token,
		})
	},
	addComment(pollId, message) {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/comment`,
			data: { message },
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.addComment.name
				].handleRequestCancellation().token,
		})
	},

	deleteComment(commentId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `comment/${commentId}`,
			params: { time: +new Date() },

			cancelToken:
				cancelTokenHandlerObject[
					this.deleteComment.name
				].handleRequestCancellation().token,
		})
	},
	restoreComment(commentId) {
		return httpInstance.request({
			method: 'PUT',
			url: `comment/${commentId}/restore`,
			params: { time: +new Date() },

			cancelToken:
				cancelTokenHandlerObject[
					this.restoreComment.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(comments)

export default comments
