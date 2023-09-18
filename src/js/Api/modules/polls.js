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

	updatePoll(poll) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${poll.id}`,
			data: { poll },
			cancelToken: cancelTokenHandlerObject[this.updatePoll.name].handleRequestCancellation().token,
		})
	},

	deletePoll(pollId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}`,
			cancelToken: cancelTokenHandlerObject[this.deletePoll.name].handleRequestCancellation().token,
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
