/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { AxiosResponse } from '@nextcloud/axios'
import { Option, Sequence, SimpleOption } from '../../stores/options.js'
import { Session } from '../../stores/session.js'
import { Answer, Vote } from '../../stores/votes.js'
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'
import { Comment } from '../../stores/comments.js'
import { Poll } from '../../stores/poll.js'
import { Share } from '../../stores/shares.js'
import { SentResults } from './shares.js'

const publicPoll = {
	getPoll(shareToken: string): Promise<
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
			url: `/s/${shareToken}/poll`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getPoll.name
				].handleRequestCancellation().token,
		})
	},

	getSession(shareToken: string): Promise<AxiosResponse<Session>> {
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

	getOptions(shareToken: string): Promise<AxiosResponse<{ options: Option[] }>> {
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

	addOption(
		shareToken: string,
		option: SimpleOption,
		sequence: Sequence | null,
		voteYes: boolean = false,
	): Promise<
		AxiosResponse<{
			option: Option
			repetitions: Option[]
			options: Option[]
			votes: Vote[]
		}>
	> {
		return httpInstance.request({
			method: 'POST',
			url: `/s/${shareToken}/option`,
			data: { option, sequence, voteYes },
			cancelToken:
				cancelTokenHandlerObject[
					this.addOption.name
				].handleRequestCancellation().token,
		})
	},

	deleteOption(
		shareToken: string,
		optionId: number,
	): Promise<AxiosResponse<{ option: Option }>> {
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

	restoreOption(
		shareToken: string,
		optionId: number,
	): Promise<AxiosResponse<{ option: Option }>> {
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

	getVotes(shareToken: string): Promise<AxiosResponse<{ votes: Vote[] }>> {
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

	setVote(
		shareToken: string,
		optionId: number,
		setTo: Answer,
	): Promise<
		AxiosResponse<{ vote: Vote; poll: Poll; options: Option[]; votes: Vote[] }>
	> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}/vote`,
			data: {
				optionId,
				setTo,
			},
			cancelToken:
				cancelTokenHandlerObject[
					this.setVote.name
				].handleRequestCancellation().token,
		})
	},

	resetVotes(
		shareToken: string,
	): Promise<AxiosResponse<{ poll: Poll; options: Option[]; votes: Vote[] }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/user`,
			cancelToken:
				cancelTokenHandlerObject[
					this.resetVotes.name
				].handleRequestCancellation().token,
		})
	},

	removeOrphanedVotes(
		shareToken: string,
	): Promise<AxiosResponse<{ poll: Poll; options: Option[]; votes: Vote[] }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `s/${shareToken}/votes/orphaned`,
			cancelToken:
				cancelTokenHandlerObject[
					this.removeOrphanedVotes.name
				].handleRequestCancellation().token,
		})
	},

	getComments(
		shareToken: string,
	): Promise<AxiosResponse<{ comments: Comment[] }>> {
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

	addComment(
		shareToken: string,
		message: string,
	): Promise<AxiosResponse<{ comment: Comment }>> {
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

	deleteComment(
		shareToken: string,
		commentId: number,
	): Promise<AxiosResponse<{ comment: Comment }>> {
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

	restoreComment(
		shareToken: string,
		commentId: number,
	): Promise<AxiosResponse<{ comment: Comment }>> {
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

	getShare(shareToken: string): Promise<AxiosResponse<{ share: Share }>> {
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

	setEmailAddress(
		shareToken: string,
		emailAddress: string,
	): Promise<AxiosResponse<{ share: Share }>> {
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

	deleteEmailAddress(
		shareToken: string,
	): Promise<AxiosResponse<{ share: Share }>> {
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

	setDisplayName(
		shareToken: string,
		displayName: string,
	): Promise<AxiosResponse<{ share: Share }>> {
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

	resendInvitation(
		shareToken: string,
	): Promise<AxiosResponse<{ share: Share; sentResult: SentResults }>> {
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

	getSubscription(
		shareToken: string,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'GET',
			url: `s/${shareToken}/subscription`,
			cancelToken:
				cancelTokenHandlerObject[
					this.getSubscription.name
				].handleRequestCancellation().token,
		})
	},

	setSubscription(
		shareToken: string,
		subscription: boolean,
	): Promise<AxiosResponse<{ subscribed: boolean }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `s/${shareToken}${subscription ? '/subscribe' : '/unsubscribe'}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.setSubscription.name
				].handleRequestCancellation().token,
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
			cancelToken:
				cancelTokenHandlerObject[
					this.register.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(publicPoll)

export default publicPoll
