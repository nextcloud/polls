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

const shares = {
	getShares(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/shares`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getShares.name].handleRequestCancellation().token,
		})
	},

	addShare(pollId, share) {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/share`,
			data: {
				...share,
			},
			cancelToken: cancelTokenHandlerObject[this.addShare.name].handleRequestCancellation().token,
		})
	},

	writeLabel(shareToken, displayName) {
		return httpInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/setlabel`,
			data: {
				label: displayName,
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
