import type { AxiosResponse } from '@nextcloud/axios'
import type {
	PublicPollEmailConditions,
	Share,
	SharePurpose,
	ShareType,
} from '../../stores/shares.types'
import type { User } from '../../Types'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi'

export type SentResults = {
	sentMails: { emailAddress: string; displayName: string }[]
	abortedMails: { emailAddress: string; displayName: string }[]
}

const shares = {
	getShares(
		pollOrPollGroupId: number,
		purpose: SharePurpose = 'poll',
	): Promise<AxiosResponse<{ shares: Share[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `${purpose.toLowerCase()}/${pollOrPollGroupId}/shares`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getShares.name
			].handleRequestCancellation().signal,
		})
	},

	addUserShare(
		pollOrPollGroupId: number,
		user: User,
		purpose: SharePurpose = 'poll',
	): Promise<AxiosResponse<{ share: Share }>> {
		// make purpose lower case
		return httpInstance.request({
			method: 'POST',
			url: `${purpose.toLowerCase()}/${pollOrPollGroupId}/share`,
			data: user,
			signal: cancelTokenHandlerObject[
				this.addUserShare.name
			].handleRequestCancellation().signal,
		})
	},

	addPublicShare(pollId: number): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/publicshare`,
			signal: cancelTokenHandlerObject[
				this.addPublicShare.name
			].handleRequestCancellation().signal,
		})
	},

	writeLabel(
		shareToken: string,
		label: string,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/setlabel`,
			data: {
				label,
			},
			signal: cancelTokenHandlerObject[
				this.writeLabel.name
			].handleRequestCancellation().signal,
		})
	},

	switchAdmin(
		shareToken: string,
		setTo: ShareType,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/${setTo}`,
			signal: cancelTokenHandlerObject[
				this.switchAdmin.name
			].handleRequestCancellation().signal,
		})
	},

	setEmailAddressConstraint(
		shareToken: string,
		setTo: PublicPollEmailConditions,
	): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/publicpollemail/${setTo}`,
			signal: cancelTokenHandlerObject[
				this.setEmailAddressConstraint.name
			].handleRequestCancellation().signal,
		})
	},

	sendInvitation(shareToken: string): Promise<
		AxiosResponse<{
			share: Share
			sentResult: null | SentResults
		}>
	> {
		return httpInstance.request({
			method: 'POST',
			url: `share/${shareToken}/invite`,
			signal: cancelTokenHandlerObject[
				this.sendInvitation.name
			].handleRequestCancellation().signal,
		})
	},

	resolveShare(shareToken: string): Promise<AxiosResponse> {
		return httpInstance.request({
			method: 'GET',
			url: `share/${shareToken}/resolve`,
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.resolveShare.name
			].handleRequestCancellation().signal,
		})
	},

	deleteShare(shareToken: string): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `share/${shareToken}`,
			signal: cancelTokenHandlerObject[
				this.deleteShare.name
			].handleRequestCancellation().signal,
		})
	},

	restoreShare(shareToken: string): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/restore`,
			signal: cancelTokenHandlerObject[
				this.restoreShare.name
			].handleRequestCancellation().signal,
		})
	},

	lockShare(shareToken: string): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/lock`,
			signal: cancelTokenHandlerObject[
				this.lockShare.name
			].handleRequestCancellation().signal,
		})
	},

	unlockShare(shareToken: string): Promise<AxiosResponse<{ share: Share }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/unlock`,
			signal: cancelTokenHandlerObject[
				this.unlockShare.name
			].handleRequestCancellation().signal,
		})
	},

	inviteAll(
		pollId: number,
	): Promise<AxiosResponse<{ poll: number; sentResult: SentResults }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/inviteAll`,
			signal: cancelTokenHandlerObject[
				this.inviteAll.name
			].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(shares)

export default shares
