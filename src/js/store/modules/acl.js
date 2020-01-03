/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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

import axios from '@nextcloud/axios'

const defaultAcl = () => {
	return {
		userId: OC.getCurrentUser().uid,
		pollId: null,
		token: null,
		isOwner: false,
		isAdmin: false,
		allowView: false,
		allowVote: false,
		allowComment: false,
		allowEdit: false,
		allowSeeUsernames: false,
		allowSeeAllVotes: false,
		foundByToken: false,
		accessLevel: ''
	}
}

const state = defaultAcl()

const mutations = {

	setAcl(state, payload) {
		Object.assign(state, payload.acl)
	},

	resetAcl(state) {
		Object.assign(state, defaultAcl())
	}

}

const actions = {

	loadAcl(context, payload) {
		let endPoint = 'apps/polls/acl/get/'

		if (payload.token !== undefined) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			context.commit('resetAcl')
			return
		}

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				context.commit('setAcl', { acl: response.data })
			}, (error) => {
				console.error('Error loading comments', { error: error.response }, { payload: payload })
				throw error
			})
	}
}

export default { state, mutations, actions }
