import type { AxiosResponse } from '@nextcloud/axios'
import type { Comment } from '../../stores/comments.types'
import type { OptionDto, Sequence, SimpleOption } from '../../stores/options.types'
import type { Session } from '../../stores/session.types'
import type { Share } from '../../stores/shares.types'
import type { Answer, Vote } from '../../stores/votes.types'
import type {
	AddOptionResponse,
	FullPollResponse,
	RemoveVotesResponse,
	setVoteResponse,
} from './api.types'
import type { SentResults } from './shares'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi'

const publicPoll = {
	getPoll(shareToken: string): Promise<AxiosResponse<FullPollResponse>> {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/poll`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getPoll.name
			].handleRequestCancellation().signal,
		})
	},

	getSession(shareToken: string): Promise<AxiosResponse<Session>> {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/session`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getSession.name
			].handleRequestCancellation().signal,
		})
	},

	getOptions(
		shareToken: string,
	): Promise<AxiosResponse<{ options: OptionDto[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/options`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getOptions.name
			].handleRequestCancellation().signal,
		})
	},

	addOption(
		shareToken: string,
		option: SimpleOption,
		sequence: Sequence | null,
		voteYes: boolean = false,
	): Promise<AxiosResponse<AddOptionResponse>> {
		return httpInstance.request({
			method: 'POST',
			url: `/s/${shareToken}/option`,
			data: { option, sequence, voteYes },
			signal: cancelTokenHandlerObject[
				this.addOption.name
			].handleRequestCancellation().signal,
		})
	},

	deleteOption(
		shareToken: string,
		optionId: number,
	): Promise<AxiosResponse<{ option: OptionDto }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/option/${optionId}`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.deleteOption.name
			].handleRequestCancellation().signal,
		})
	},

	restoreOption(
		shareToken: string,
		optionId: number,
	): Promise<AxiosResponse<{ option: OptionDto }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/option/${optionId}/restore`,
			params: { time: +new Date() },

			signal: cancelTokenHandlerObject[
				this.restoreOption.name
			].handleRequestCancellation().signal,
		})
	},

	getVotes(shareToken: string): Promise<AxiosResponse<{ votes: Vote[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/votes`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getVotes.name
			].handleRequestCancellation().signal,
		})
	},

	setVote(
		shareToken: string,
		optionId: number,
		setTo: Answer,
	): Promise<AxiosResponse<setVoteResponse>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/vote`,
			data: {
				optionId,
				setTo,
			},
			signal: cancelTokenHandlerObject[
				this.setVote.name
			].handleRequestCancellation(optionId).signal,
		})
	},

	resetVotes(shareToken: string): Promise<AxiosResponse<RemoveVotesResponse>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/user`,
			signal: cancelTokenHandlerObject[
				this.resetVotes.name
			].handleRequestCancellation().signal,
		})
	},

	removeOrphanedVotes(
		shareToken: string,
	): Promise<AxiosResponse<RemoveVotesResponse>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/votes/orphaned`,
			signal: cancelTokenHandlerObject[
				this.removeOrphanedVotes.name
			].handleRequestCancellation().signal,
		})
	},

	getComments(
		shareToken: string,
	): Promise<AxiosResponse<{ comments: Comment[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `/s/${shareToken}/comments`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getComments.name
			].handleRequestCancellation().signal,
		})
	},

	addComment(
		shareToken: string,
		message: string,
		confidential: boolean = false,
	): Promise<AxiosResponse<{ comment: Comment }>> {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/comment`,
			data: { message, confidential },
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.addComment.name
			].handleRequestCancellation().signal,
		})
	},

	deleteComment(
		shareToken: string,
		commentId: number,
	): Promise<AxiosResponse<{ comment: Comment }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/comment/${commentId}`,
			params: { time: +new Date() },

			signal: cancelTokenHandlerObject[
				this.deleteComment.name
			].handleRequestCancellation().signal,
		})
	},

	restoreComment(
		shareToken: string,
		commentId: number,
	): Promise<AxiosResponse<{ comment: Comment }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/comment/${commentId}/restore`,
			params: { time: +new Date() },

			signal: cancelTokenHandlerObject[
				this.restoreComment.name
			].handleRequestCancellation().signal,
		})
	},

	getShare(shareToken: string): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/share`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getShare.name
			].handleRequestCancellation().signal,
		})
	},

	setEmailAddress(
		shareToken: string,
		emailAddress: string,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/email/${emailAddress}`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.setEmailAddress.name
			].handleRequestCancellation().signal,
		})
	},

	deleteEmailAddress(
		shareToken: string,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/email`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.deleteEmailAddress.name
			].handleRequestCancellation().signal,
		})
	},

	setDisplayName(
		shareToken: string,
		displayName: string,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/name/${displayName}`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.setDisplayName.name
			].handleRequestCancellation().signal,
		})
	},

	resendInvitation(
		shareToken: string,
	): Promise<AxiosResponse<{ share: Share; sentResult: SentResults }>> {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/resend`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.resendInvitation.name
			].handleRequestCancellation().signal,
		})
	},

	getSubscription(
		shareToken: string,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/subscription`,
			signal: cancelTokenHandlerObject[
				this.getSubscription.name
			].handleRequestCancellation().signal,
		})
	},

	setSubscription(
		shareToken: string,
		subscription: boolean,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}${subscription ? '/subscribe' : '/unsubscribe'}`,
			signal: cancelTokenHandlerObject[
				this.setSubscription.name
			].handleRequestCancellation().signal,
		})
	},

	register(
		shareToken: string,
		displayName: string,
		emailAddress: string,
		timeZone: undefined = undefined,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'POST',
			url: `s/${shareToken}/register`,
			data: {
				displayName,
				emailAddress,
				timeZone,
			},
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.register.name
			].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(publicPoll)

export default publicPoll
