/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi'

import type { AxiosResponse } from '@nextcloud/axios'
import type { ApiEmailAdressList, FullPollResponse } from './api.types'
import type { PollGroup } from '../../stores/pollGroups.types'
import type { Poll, PollConfiguration, PollType } from '../../stores/poll.types'

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
			cancelToken:
				cancelTokenHandlerObject[
					this.getPolls.name
				].handleRequestCancellation().token,
		})
	},

	getPoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/poll`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getPoll.name
				].handleRequestCancellation().token,
		})
	},

	getFullPoll(pollId: number): Promise<AxiosResponse<FullPollResponse>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getPoll.name
				].handleRequestCancellation().token,
		})
	},

	takeOver(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `administration/poll/${pollId}/takeover`,
			cancelToken:
				cancelTokenHandlerObject[
					this.takeOver.name
				].handleRequestCancellation().token,
		})
	},

	changeOwner(
		pollId: number,
		userId: string,
	): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/changeowner/${userId}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.changeOwner.name
				].handleRequestCancellation().token,
		})
	},

	addPoll(type: PollType, title: string): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'poll/add',
			data: {
				type,
				title,
			},
			cancelToken:
				cancelTokenHandlerObject[
					this.addPoll.name
				].handleRequestCancellation().token,
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
			cancelToken:
				cancelTokenHandlerObject[
					this.writePoll.name
				].handleRequestCancellation().token,
		})
	},

	lockAnonymous(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/lockAnonymous`,
			cancelToken:
				cancelTokenHandlerObject[
					this.lockAnonymous.name
				].handleRequestCancellation().token,
		})
	},

	deletePoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.deletePoll.name
				].handleRequestCancellation().token,
		})
	},

	closePoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/close`,
			cancelToken:
				cancelTokenHandlerObject[
					this.closePoll.name
				].handleRequestCancellation().token,
		})
	},

	reopenPoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/reopen`,
			cancelToken:
				cancelTokenHandlerObject[
					this.reopenPoll.name
				].handleRequestCancellation().token,
		})
	},

	toggleArchive(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/toggleArchive`,
			cancelToken:
				cancelTokenHandlerObject[
					this.toggleArchive.name
				].handleRequestCancellation().token,
		})
	},

	clonePoll(pollId: number): Promise<AxiosResponse<{ poll: Poll }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/clone`,
			cancelToken:
				cancelTokenHandlerObject[
					this.clonePoll.name
				].handleRequestCancellation().token,
		})
	},

	sendConfirmation(
		pollId: number,
	): Promise<AxiosResponse<{ confirmations: Confirmations }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/confirmation`,
			cancelToken:
				cancelTokenHandlerObject[
					this.sendConfirmation.name
				].handleRequestCancellation().token,
		})
	},

	getParticipantsEmailAddresses(
		pollId: string | number | string[],
	): Promise<AxiosResponse<ApiEmailAdressList[]>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/addresses`,
			cancelToken:
				cancelTokenHandlerObject[
					this.getParticipantsEmailAddresses.name
				].handleRequestCancellation().token,
		})
	},

	getSubscription(
		pollId: number,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/subscription`,
			cancelToken:
				cancelTokenHandlerObject[
					this.getSubscription.name
				].handleRequestCancellation().token,
		})
	},

	setSubscription(
		pollId: number,
		subscription: boolean,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}${subscription ? '/subscribe' : '/unsubscribe'}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.setSubscription.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(polls)

export default polls
