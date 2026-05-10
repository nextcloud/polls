/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi'

import type { AxiosResponse } from '@nextcloud/axios'
import type { ApiEmailAdressList, FullPollResponse } from './api.types'
import type { PollGroup } from '../../stores/pollGroups.types'
import type { Poll, PollConfiguration, PollMandatory } from '../../stores/poll.types'

export type Confirmations = {
	sentMails: { emailAddress: string; displayName: string }[]
	abortedMails: { emailAddress: string; displayName: string; reason: string }[]
	countSentMails: number
	countAbortedMails: number
}

const polls = {
	getPolls(): Promise<
		AxiosResponse<{
			polls: Poll[]
			permissions: {
				pollCreationAllowed: boolean
				comboAllowed: true
			}
			pollGroups: PollGroup[]
		}>
	> {
		return httpInstance.request({
			method: 'GET',
			url: 'polls',
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getPolls.name
			].handleRequestCancellation().signal,
		})
	},

	getPoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/poll`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getPoll.name
			].handleRequestCancellation().signal,
		})
	},

	getFullPoll(pollId: number): Promise<AxiosResponse<FullPollResponse>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getPoll.name
			].handleRequestCancellation().signal,
		})
	},

	takeOver(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `administration/poll/${pollId}/takeover`,
			signal: cancelTokenHandlerObject[
				this.takeOver.name
			].handleRequestCancellation().signal,
		})
	},

	changeOwner(
		pollId: number,
		userId: string,
	): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/changeowner/${userId}`,
			signal: cancelTokenHandlerObject[
				this.changeOwner.name
			].handleRequestCancellation().signal,
		})
	},

	addPoll(payload: PollMandatory): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'poll/add',
			data: payload,
			signal: cancelTokenHandlerObject[
				this.addPoll.name
			].handleRequestCancellation().signal,
		})
	},

	writePoll(
		pollId: number,
		poll: PollConfiguration,
	): Promise<
		AxiosResponse<{
			poll: Poll
			diff: Partial<Poll>
			changes: Partial<Poll>
		}>
	> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}`,
			data: { poll },
			signal: cancelTokenHandlerObject[
				this.writePoll.name
			].handleRequestCancellation().signal,
		})
	},

	lockAnonymous(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/lockAnonymous`,
			signal: cancelTokenHandlerObject[
				this.lockAnonymous.name
			].handleRequestCancellation().signal,
		})
	},

	deletePoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}`,
			signal: cancelTokenHandlerObject[
				this.deletePoll.name
			].handleRequestCancellation().signal,
		})
	},

	closePoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/close`,
			signal: cancelTokenHandlerObject[
				this.closePoll.name
			].handleRequestCancellation().signal,
		})
	},

	reopenPoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/reopen`,
			signal: cancelTokenHandlerObject[
				this.reopenPoll.name
			].handleRequestCancellation().signal,
		})
	},

	toggleArchive(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/toggleArchive`,
			signal: cancelTokenHandlerObject[
				this.toggleArchive.name
			].handleRequestCancellation().signal,
		})
	},

	clonePoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/clone`,
			signal: cancelTokenHandlerObject[
				this.clonePoll.name
			].handleRequestCancellation().signal,
		})
	},

	sendConfirmation(
		pollId: number,
	): Promise<AxiosResponse<{ confirmations: Confirmations }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/confirmation`,
			signal: cancelTokenHandlerObject[
				this.sendConfirmation.name
			].handleRequestCancellation().signal,
		})
	},

	getParticipantsEmailAddresses(
		pollId: string | number | string[],
	): Promise<AxiosResponse<ApiEmailAdressList[]>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/addresses`,
			signal: cancelTokenHandlerObject[
				this.getParticipantsEmailAddresses.name
			].handleRequestCancellation().signal,
		})
	},

	getSubscription(
		pollId: number,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/subscription`,
			signal: cancelTokenHandlerObject[
				this.getSubscription.name
			].handleRequestCancellation().signal,
		})
	},

	setSubscription(
		pollId: number,
		subscription: boolean,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}${subscription ? '/subscribe' : '/unsubscribe'}`,
			signal: cancelTokenHandlerObject[
				this.setSubscription.name
			].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(polls)

export default polls
