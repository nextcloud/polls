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

const votes = {
	getVotes(pollId) {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/votes`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getVotes.name].handleRequestCancellation().token,
		})
	},

	setVote(optionId, setTo) {
		return httpInstance.request({
			method: 'PUT',
			url: 'vote',
			data: { optionId, setTo },
			cancelToken: cancelTokenHandlerObject[this.setVote.name].handleRequestCancellation().token,
		})
	},

	removeUser(pollId, userId = null) {
		return httpInstance.request({
			method: 'DELETE',
			url: userId ? `poll/${pollId}/user/${userId}` : `poll/${pollId}/user`,
			cancelToken: cancelTokenHandlerObject[this.removeUser.name].handleRequestCancellation().token,
		})
	},

	removeOrphanedVotes(pollId) {
		return httpInstance.request({
			method: 'DELETE',
			url: `poll/${pollId}/votes/orphaned`,
			cancelToken: cancelTokenHandlerObject[this.removeOrphanedVotes.name].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(votes)

export default votes
