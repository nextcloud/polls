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
	allowAddOptions: false,
	allowAllAccess: false,
	allowArchive: false,
	allowComment: false,
	allowDelete: false,
	allowEdit: false,
	allowPublicShares: false,
	allowSeeResults: false,
	allowSeeUsernames: false,
	allowSubscribe: false,
	allowView: false,
	allowVote: false,
	displayName: '',
	isOwner: false,
	loggedIn: false,
	pollId: null,
	token: '',
	userHasVoted: false,
	userId: '',
	userIsInvolved: '',
})

const state = defaultAcl()

const namespaced = true

const mutations = {

	set(state, payload) {
		Object.assign(state, payload.acl)
	},

	reset(state) {
		Object.assign(state, defaultAcl())
	},

}

export default { namespaced, state, mutations }
