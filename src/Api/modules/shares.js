/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const shares = {
	getShares(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/shares`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getShares.name].handleRequestCancellation().token,
		})
	},

	addUserShare(pollId, user) {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/share`,
			data: user,
			cancelToken: cancelTokenHandlerObject[this.addUserShare.name].handleRequestCancellation().token,
		})
	},

	addPublicShare(pollId) {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/publicshare`,
			cancelToken: cancelTokenHandlerObject[this.addPublicShare.name].handleRequestCancellation().token,
		})
	},

	writeLabel(shareToken, label) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/setlabel`,
			data: {
				label,
			},
			cancelToken: cancelTokenHandlerObject[this.writeLabel.name].handleRequestCancellation().token,
		})
	},

	switchAdmin(shareToken, setTo) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/${setTo}`,
			cancelToken: cancelTokenHandlerObject[this.switchAdmin.name].handleRequestCancellation().token,
		})
	},

	setEmailAddressConstraint(shareToken, setTo) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/publicpollemail/${setTo}`,
			cancelToken: cancelTokenHandlerObject[this.setEmailAddressConstraint.name].handleRequestCancellation().token,
		})
	},

	sendInvitation(shareToken) {
		return httpInstance.request({
			method: 'POST',
			url: `share/${shareToken}/invite`,
			cancelToken: cancelTokenHandlerObject[this.sendInvitation.name].handleRequestCancellation().token,
		})
	},

	resolveShare(shareToken) {
		return httpInstance.request({
			method: 'GET',
			url: `share/${shareToken}/resolve`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.resolveShare.name].handleRequestCancellation().token,
		})
	},

	deleteShare(shareToken) {
		return httpInstance.request({
			method: 'DELETE',
			url: `share/${shareToken}`,
			cancelToken: cancelTokenHandlerObject[this.deleteShare.name].handleRequestCancellation().token,
		})
	},

	restoreShare(shareToken) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/restore`,
			cancelToken: cancelTokenHandlerObject[this.restoreShare.name].handleRequestCancellation().token,
		})
	},

	lockShare(shareToken) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/lock`,
			cancelToken: cancelTokenHandlerObject[this.lockShare.name].handleRequestCancellation().token,
		})
	},

	unlockShare(shareToken) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/unlock`,
			cancelToken: cancelTokenHandlerObject[this.unlockShare.name].handleRequestCancellation().token,
		})
	},

	inviteAll(pollId) {
		return httpInstance.request({
			method: 'PUT',
			url: `poll/${pollId}/inviteAll`,
			cancelToken: cancelTokenHandlerObject[this.inviteAll.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(shares)

export default shares
