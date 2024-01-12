/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2020 Rene Gieling <github@dartcafe.de>
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

const defaultAcl = () => ({
	isVoteLimitExceeded: false,
	pollId: null,
	pollExpired: false,
	pollExpire: 0,
	token: '',
	currentUser: {
		displayName: '',
		hasVoted: false,
		isInvolved: false,
		isLoggedIn: false,
		isNoUser: true,
		isOwner: false,
		userId: '',
	},
	permissions: {
		addOptions: false,
		allAccess: false,
		archive: false,
		comment: false,
		delete: false,
		edit: false,
		pollCreation: false,
		pollDownload: false,
		publicShares: false,
		seeResults: false,
		seeUsernames: false,
		seeMailAddresses: false,
		subscribe: false,
		view: false,
		vote: false,
	},
})

const namespaced = true
const state = defaultAcl()

const mutations = {

	set(state, payload) {
		Object.assign(state, payload.acl)
	},

	reset(state) {
		Object.assign(state, defaultAcl())
	},

}

export default { namespaced, state, mutations }
