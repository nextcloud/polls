/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const polls = {
	getPolls() {
		return httpInstance.request({
			method: 'GET',
			url: 'polls',
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getPolls.name].handleRequestCancellation().token,
		})
	},

	getPollsForAdmin() {
		return httpInstance.request({
			method: 'GET',
			url: 'administration/polls',
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getPollsForAdmin.name].handleRequestCancellation().token,
		})
	},

	getPoll(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/poll`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getPoll.name].handleRequestCancellation().token,
		})
	},

	getFullPoll(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getPoll.name].handleRequestCancellation().token,
		})
	},

	watchPoll(pollId = 0, lastUpdated) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/watch`,
			params: { offset: lastUpdated },
			cancelToken: cancelTokenHandlerObject[this.watchPoll.name].handleRequestCancellation().token,
		})
	},

	takeOver(pollId) {
		return httpInstance.request({
			method: 'PUT',
			url: `administration/poll/${pollId}/takeover`,
			cancelToken: cancelTokenHandlerObject[this.takeOver.name].handleRequestCancellation().token,
		})
	},

	addPoll(type, title) {
		return httpInstance.request({
			method: 'POST',
			url: 'poll/add',
			data: {
				type,
				title,
			},
			cancelToken: cancelTokenHandlerObject[this.addPoll.name].handleRequestCancellation().token,
		})
	},

	writePoll(pollId, poll) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}`,
			data: { poll },
			cancelToken: cancelTokenHandlerObject[this.writePoll.name].handleRequestCancellation().token,
		})
	},

	lockAnonymous(pollId) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/lockAnonymous`,
			cancelToken: cancelTokenHandlerObject[this.lockAnonymous.name].handleRequestCancellation().token,
		})
	},

	deletePoll(pollId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}`,
			cancelToken: cancelTokenHandlerObject[this.deletePoll.name].handleRequestCancellation().token,
		})
	},

	closePoll(pollId) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/close`,
			cancelToken: cancelTokenHandlerObject[this.closePoll.name].handleRequestCancellation().token,
		})
	},

	reopenPoll(pollId) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/reopen`,
			cancelToken: cancelTokenHandlerObject[this.reopenPoll.name].handleRequestCancellation().token,
		})
	},

	toggleArchive(pollId) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/toggleArchive`,
			cancelToken: cancelTokenHandlerObject[this.toggleArchive.name].handleRequestCancellation().token,
		})
	},

	clonePoll(pollId) {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/clone`,
			cancelToken: cancelTokenHandlerObject[this.clonePoll.name].handleRequestCancellation().token,
		})
	},

	sendConfirmation(pollId) {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/confirmation`,
			cancelToken: cancelTokenHandlerObject[this.sendConfirmation.name].handleRequestCancellation().token,
		})
	},

	getParticipantsEmailAddresses(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/addresses`,
			cancelToken: cancelTokenHandlerObject[this.getParticipantsEmailAddresses.name].handleRequestCancellation().token,
		})
	},

	getSubscription(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/subscription`,
			cancelToken: cancelTokenHandlerObject[this.getSubscription.name].handleRequestCancellation().token,
		})
	},

	setSubscription(pollId, subscription) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}${subscription ? '/subscribe' : '/unsubscribe'}`,
			cancelToken: cancelTokenHandlerObject[this.setSubscription.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(polls)

export default polls
