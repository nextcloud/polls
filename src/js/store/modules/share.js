/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2021 Rene Gieling <github@dartcafe.de>
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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { setCookie } from '../../helpers/cookieHelper'

const defaultShares = () => ({
	displayName: '',
	id: null,
	invitationSent: 0,
	pollId: null,
	token: '',
	type: '',
	emailAddress: '',
	userId: '',
	publicPollEmail: 'optional',
})

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
		if (context.rootState.route.name !== 'publicVote') {
			context.commit('reset')
			return
		}

		const endPoint = `apps/polls/s/${context.rootState.route.params.token}/share`

		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('set', { share: response.data.share })
			return response.data
		} catch (e) {
			console.debug('Error retrieving share', { error: e.response })
			throw e.response
		}
	},

	async register(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		const endPoint = `apps/polls/s/${context.rootState.route.params.token}/register`

		try {
			const response = await axios.post(generateUrl(endPoint), {
				userName: payload.userName,
				emailAddress: payload.emailAddress,
			})

			if (payload.saveCookie) {
				const cookieExpiration = (30 * 24 * 60 * 1000)
				setCookie(context.rootState.route.params.token, response.data.share.token, cookieExpiration)
			}

			return { token: response.data.share.token }

		} catch (e) {
			console.error('Error writing personal share', { error: e.response }, { payload })
			throw e
		}
	},

	async updateEmailAddress(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		const endPoint = `apps/polls/s/${context.rootState.route.params.token}/email`

		try {
			const response = await axios.put(generateUrl(endPoint), {
				emailAddress: payload.emailAddress,
			})
			context.commit('set', { share: response.data.share })
			context.dispatch('poll/get', null, { root: true })
		} catch (e) {
			console.error('Error writing email address', { error: e.response }, { payload })
			throw e
		}
	},

	async deleteEmailAddress(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		const endPoint = `apps/polls/s/${context.rootState.route.params.token}/email`

		try {
			const response = await axios.delete(generateUrl(endPoint))
			context.commit('set', { share: response.data.share })
			context.dispatch('subscription/update', false, { root: true })
			context.dispatch('poll/get', null, { root: true })
		} catch (e) {
			console.error('Error writing email address', { error: e.response }, { payload })
			throw e
		}
	},

	async resendInvitation(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		const endPoint = `apps/polls/s/${context.rootState.route.params.token}/resend`

		try {
			return await axios.put(generateUrl(endPoint))
		} catch (e) {
			console.error('Error sending invitation', { error: e.response }, { payload })
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions }
