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

import { axiosInstance, createCancelTokenHandler } from './AxiosHelper.js'

const shares = {
	getShares(pollId) {
		return axiosInstance.request({
			method: 'GET',
			url: `poll/${pollId}/shares`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getShares.name].handleRequestCancellation().token,
		})
	},

	addShare(pollId, share) {
		return axiosInstance.request({
			method: 'POST',
			url: `poll/${pollId}/share`,
			data: {
				...share,
			},
			cancelToken: cancelTokenHandlerObject[this.addShare.name].handleRequestCancellation().token,
		})
	},

	switchAdmin(shareToken, setTo) {
		return axiosInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/${setTo}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.switchAdmin.name].handleRequestCancellation().token,
		})
	},

	setEmailAddressConstraint(shareToken, setTo) {
		return axiosInstance.request({
			method: 'PUT',
			url: `share/${shareToken}/publicpollemail/${setTo}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.setEmailAddressConstraint.name].handleRequestCancellation().token,
		})
	},

	sendInvitation(shareToken) {
		return axiosInstance.request({
			method: 'POST',
			url: `share/${shareToken}/invite`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.sendInvitation.name].handleRequestCancellation().token,
		})
	},

	resolveShare(shareToken) {
		return axiosInstance.request({
			method: 'GET',
			url: `share/${shareToken}/resolve`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.resolveShare.name].handleRequestCancellation().token,
		})
	},

	deleteShare(shareToken) {
		return axiosInstance.request({
			method: 'DELETE',
			url: `share/${shareToken}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.deleteShare.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(shares)

export { shares as SharesAPI }
