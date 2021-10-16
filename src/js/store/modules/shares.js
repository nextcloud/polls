/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
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

const defaultShares = () => ({
	list: [],
})

const state = defaultShares()

const namespaced = true

const mutations = {
	set(state, payload) {
		state.list = payload.shares
	},

	delete(state, payload) {
		state.list = state.list.filter((share) => share.id !== payload.share.id)
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},

	add(state, payload) {
		state.list.push(payload)
	},

	update(state, payload) {
		const foundIndex = state.list.findIndex((share) => share.id === payload.share.id)
		Object.assign(state.list[foundIndex], payload.share)
	},

}

const getters = {
	invitation: (state) => {
		// share types, which will be active, after the user gets his invitation
		const invitationTypes = ['email', 'external', 'contact']
		// sharetype which are active without sending an invitation
		const directShareTypes = ['user', 'group', 'admin']
		return state.list.filter((share) => (invitationTypes.includes(share.type) && (share.type === 'external' || share.invitationSent)) || directShareTypes.includes(share.type))
	},

	unsentInvitations: (state) => state.list.filter((share) => (share.emailAddress || share.type === 'group' || share.type === 'contactGroup' || share.type === 'circle') && !share.invitationSent),
	public: (state) => state.list.filter((share) => ['public'].includes(share.type)),
}

const actions = {
	async list(context) {
		const endPoint = `apps/polls/poll/${context.rootState.route.params.id}/shares`

		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('set', response.data)
		} catch (e) {
			console.error('Error loading shares', { error: e.response }, { pollId: context.rootState.route.params.id })
			throw e
		}
	},

	async add(context, payload) {
		const endPoint = `apps/polls/poll/${context.rootState.route.params.id}`

		try {
			await axios.post(generateUrl(`${endPoint}/share`), payload.share)
		} catch (e) {
			console.error('Error writing share', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('list')
		}
	},

	async delete(context, payload) {
		const endPoint = `apps/polls/share/${payload.share.token}`

		context.commit('delete', { share: payload.share })

		try {
			await axios.delete(generateUrl(endPoint))
		} catch (e) {
			console.error('Error removing share', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('list')
		}
	},

	async switchAdmin(context, payload) {
		let endPoint = `apps/polls/share/${payload.share.token}`

		if (payload.share.type === 'admin') {
			endPoint = `${endPoint}/user`
		} else if (payload.share.type === 'user') {
			endPoint = `${endPoint}/admin`
		}

		try {
			await axios.put(generateUrl(endPoint))
		} catch (e) {
			console.error('Error switching type', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('list')
		}
	},

	async sendInvitation(context, payload) {
		const endPoint = `apps/polls/share/${payload.share.token}/invite`

		try {
			return await axios.post(generateUrl(endPoint))
		} catch (e) {
			console.error('Error sending invitation', { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('list')
		}
	},

	async resolveGroup(context, payload) {
		const endPoint = `apps/polls/share/${payload.share.token}/resolve`

		try {
			await axios.get(generateUrl(endPoint))
		} catch (e) {
			console.error('Error exploding group', e.response.data, { error: e.response }, { payload })
			throw e
		} finally {
			context.dispatch('list')
		}
	},
}

export default { namespaced, state, mutations, actions, getters }
