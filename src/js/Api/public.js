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

const publicPoll = {
	getPoll(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/poll`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getPoll.name].handleRequestCancellation().token,
		})
	},

	watchPoll(shareToken, lastUpdated) {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/watch`,
			params: { offset: lastUpdated },
			cancelToken: cancelTokenHandlerObject[this.watch.name].handleRequestCancellation().token,
		})
	},

	getOptions(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/options`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getOptions.name].handleRequestCancellation().token,
		})
	},

	addOption(shareToken, option) {
		return httpInstance.request({
			method: 'POST',
			url: `/s/${shareToken}/option`,
			data: { ...option },
			cancelToken: cancelTokenHandlerObject[this.addOption.name].handleRequestCancellation().token,
		})
	},

	deleteOption(shareToken, optionId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/option/${optionId}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.deleteOption.name].handleRequestCancellation().token,
		})
	},

	getVotes(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/votes`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getVotes.name].handleRequestCancellation().token,
		})
	},

	setVote(shareToken, optionId, setTo) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/vote`,
			data: { optionId, setTo },
			cancelToken: cancelTokenHandlerObject[this.setVote.name].handleRequestCancellation().token,
		})
	},

	removeVotes(shareToken) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/user`,
			cancelToken: cancelTokenHandlerObject[this.removeVotes.name].handleRequestCancellation().token,
		})
	},

	getComments(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/comments`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getComments.name].handleRequestCancellation().token,
		})
	},

	addComment(shareToken, message) {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/comment`,
			data: { message },
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.addComment.name].handleRequestCancellation().token,
		})
	},

	deleteComment(shareToken, commentId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/${commentId}`,
			params: { time: +new Date() },

			cancelToken: cancelTokenHandlerObject[this.deleteComment.name].handleRequestCancellation().token,
		})
	},

	getShare(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/share`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getShare.name].handleRequestCancellation().token,
		})
	},

	setEmail(shareToken, emailAddress) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/email/${emailAddress}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.setEmail.name].handleRequestCancellation().token,
		})
	},

	deleteEmailAddress(shareToken) {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/email`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.setEmailAddress.name].handleRequestCancellation().token,
		})
	},

	setDisplayName(shareToken, displayName) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/name/${displayName}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.setDisplayName.name].handleRequestCancellation().token,
		})
	},

	resendInvitation(shareToken) {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/resend`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.resendInvitation.name].handleRequestCancellation().token,
		})
	},

	getSubscription(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/subscription`,
			cancelToken: cancelTokenHandlerObject[this.getSubscription.name].handleRequestCancellation().token,
		})
	},

	setSubscription(shareToken, subscription) {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}${subscription ? '/subscribe' : '/unsubscribe'}`,
			cancelToken: cancelTokenHandlerObject[this.setSubscription.name].handleRequestCancellation().token,
		})
	},

	register(shareToken, userName, emailAddress, timeZone) {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/register`,
			data: {
				userName,
				emailAddress,
				timeZone,
			},
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.register.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(publicPoll)

export { publicPoll as PublicAPI }
