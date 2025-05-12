/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Poll, PollConfiguration, PollType } from '../../stores/poll.js'
import { AxiosResponse } from '@nextcloud/axios'
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'
import { Option } from '../../stores/options.js'
import { Vote } from '../../stores/votes.js'
import { Share } from '../../stores/shares.js'
import { ApiEmailAdressList, Comment } from '../../Types/index.js'

export type Confirmations = {
	sentMails: { emailAddress: string; displayName: string }[]
	abortedMails: { emailAddress: string; displayName: string; reason: string }[]
	countSentMails: number
	countAbortedMails: number
}

export type WatcherResponse = {
	id: number
	pollId: number
	table: string
	updated: number
	sessionId: string
}

const polls = {
	getPolls(): Promise<AxiosResponse<{ list: Poll[] }>> {
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

	getFullPoll(pollId: number): Promise<
		AxiosResponse<{
			poll: Poll
			options: Option[]
			votes: Vote[]
			comments: Comment[]
			shares: Share[]
			subscribed: boolean
		}>
	> {
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

	watchPoll(
		pollId = 0,
		lastUpdated: number,
	): Promise<AxiosResponse<{ updates: WatcherResponse[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/watch`,
			params: { offset: lastUpdated },
			cancelToken:
				cancelTokenHandlerObject[
					this.watchPoll.name
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
	): Promise<AxiosResponse<{ poll: Poll }>> {
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
