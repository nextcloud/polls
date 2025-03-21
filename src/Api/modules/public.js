/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const publicPoll = {
	getPoll(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/poll`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getPoll.name
				].handleRequestCancellation().token,
		})
	},

	getSession(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/session`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getSession.name
				].handleRequestCancellation().token,
		})
	},

	watchPoll(shareToken, lastUpdated) {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/watch`,
			params: { offset: lastUpdated },
			cancelToken:
				cancelTokenHandlerObject[
					this.watchPoll.name
				].handleRequestCancellation().token,
		})
	},

	getOptions(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/options`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getOptions.name
				].handleRequestCancellation().token,
		})
	},

	addOption(shareToken, option) {
		return httpInstance.request({
			method: 'POST',
			url: `/s/${shareToken}/option`,
			data: { ...option },
			cancelToken:
				cancelTokenHandlerObject[
					this.addOption.name
				].handleRequestCancellation().token,
		})
	},

	deleteOption(shareToken, optionId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/option/${optionId}`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.deleteOption.name
				].handleRequestCancellation().token,
		})
	},

	restoreOption(shareToken, optionId) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/option/${optionId}/restore`,
			params: { time: +new Date() },

			cancelToken:
				cancelTokenHandlerObject[
					this.restoreOption.name
				].handleRequestCancellation().token,
		})
	},

	getVotes(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/votes`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getVotes.name
				].handleRequestCancellation().token,
		})
	},

	setVote(shareToken, optionId, setTo) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/vote`,
			data: { optionId, setTo },
			cancelToken:
				cancelTokenHandlerObject[
					this.setVote.name
				].handleRequestCancellation().token,
		})
	},

	removeVotes(shareToken) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/user`,
			cancelToken:
				cancelTokenHandlerObject[
					this.removeVotes.name
				].handleRequestCancellation().token,
		})
	},

	removeOrphanedVotes(shareToken) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/votes/orphaned`,
			cancelToken:
				cancelTokenHandlerObject[
					this.removeOrphanedVotes.name
				].handleRequestCancellation().token,
		})
	},

	getComments(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/comments`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getComments.name
				].handleRequestCancellation().token,
		})
	},

	addComment(shareToken, message) {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/comment`,
			data: { message },
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.addComment.name
				].handleRequestCancellation().token,
		})
	},

	deleteComment(shareToken, commentId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/comment/${commentId}`,
			params: { time: +new Date() },

			cancelToken:
				cancelTokenHandlerObject[
					this.deleteComment.name
				].handleRequestCancellation().token,
		})
	},

	restoreComment(shareToken, commentId) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/comment/${commentId}/restore`,
			params: { time: +new Date() },

			cancelToken:
				cancelTokenHandlerObject[
					this.restoreComment.name
				].handleRequestCancellation().token,
		})
	},

	getShare(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/share`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getShare.name
				].handleRequestCancellation().token,
		})
	},

	setEmailAddress(shareToken, emailAddress) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/email/${emailAddress}`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.setEmailAddress.name
				].handleRequestCancellation().token,
		})
	},

	deleteEmailAddress(shareToken) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/email`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.deleteEmailAddress.name
				].handleRequestCancellation().token,
		})
	},

	setDisplayName(shareToken, displayName) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/name/${displayName}`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.setDisplayName.name
				].handleRequestCancellation().token,
		})
	},

	resendInvitation(shareToken) {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/resend`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.resendInvitation.name
				].handleRequestCancellation().token,
		})
	},

	getSubscription(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/subscription`,
			cancelToken:
				cancelTokenHandlerObject[
					this.getSubscription.name
				].handleRequestCancellation().token,
		})
	},

	setSubscription(shareToken, subscription) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}${subscription ? '/subscribe' : '/unsubscribe'}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.setSubscription.name
				].handleRequestCancellation().token,
		})
	},

	register(shareToken, displayName, emailAddress, timeZone) {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/register`,
			data: {
				displayName,
				emailAddress,
				timeZone,
			},
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.register.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(publicPoll)

export default publicPoll
