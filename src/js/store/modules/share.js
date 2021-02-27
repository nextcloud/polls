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
import { generateUrl } from '@nextcloud/router'

const defaultShares = () => {
	return {
		displayName: '',
		id: null,
		invitationSent: 0,
		pollId: null,
		token: '',
		type: '',
		emailAddress: '',
		userId: '',
	}
}

const state = defaultShares()

const namespaced = true

const mutations = {
	set(state, payload) {
		Object.assign(state, payload.share)
	},

	setEmailAddress(state, payload) {
		state.emailAddress = payload
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},
}

const actions = {
	async get(context) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else {
			context.commit('reset')
			return
		}
		try {
			const response = await axios.get(generateUrl(endPoint + '/share'))
			context.commit('set', { share: response.data.share })
			return response.data
		} catch (e) {
			console.debug('Error retrieving share', { error: e.response })
			throw e.response
		}
	},

	async register(context, payload) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else {
			return
		}
		try {
			const response = await axios.post(generateUrl(endPoint + '/register'), {
				userName: payload.userName,
				emailAddress: payload.emailAddress,
			})
			return { token: response.data.share.token }
		} catch (e) {
			console.error('Error writing personal share', { error: e.response }, { payload: payload })
			throw e
		}
	},

	async updateEmailAddress(context, payload) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else {
			return
		}
		try {
			const response = await axios.put(generateUrl(endPoint + '/email'), {
				emailAddress: payload.emailAddress,
			})
			context.commit('set', { share: response.data.share })
		} catch (e) {
			console.error('Error writing email address', { error: e.response }, { payload: payload })
			throw e
		}
	},

	async resendInvitation(context, payload) {
		let endPoint = 'apps/polls'
		if (context.rootState.route.name === 'publicVote') {
			endPoint = endPoint + '/s/' + context.rootState.route.params.token
		} else {
			return
		}
		try {
			return await axios.get(generateUrl(endPoint + '/resend'))
		} catch (e) {
			console.error('Error sending invitation', { error: e.response }, { payload: payload })
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions }
