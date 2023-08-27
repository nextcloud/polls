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

import { SharesAPI } from '../../Api/shares.js'

const defaultShares = () => ({
	list: [],
})

const namespaced = true
const state = defaultShares()

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

	updateShare(state, payload) {
		const foundIndex = state.list.findIndex((share) => share.id === payload.share.id)
		Object.assign(state.list[foundIndex], payload.share)
	},

	setShareProperty(state, payload) {
		const foundIndex = state.list.findIndex((share) => share.id === payload.id)
		Object.assign(state.list[foundIndex], payload)
	},

}

const getters = {
	invitation: (state) => {
		// share types, which will be active, after the user gets his invitation
		const invitationTypes = ['email', 'external', 'contact']
		// sharetype which are active without sending an invitation
		const directShareTypes = ['user', 'group', 'admin', 'public']
		return state.list.filter((share) => (invitationTypes.includes(share.type) && (share.type === 'external' || share.invitationSent)) || directShareTypes.includes(share.type))
	},

	unsentInvitations: (state) => state.list.filter((share) => (share.emailAddress || share.type === 'group' || share.type === 'contactGroup' || share.type === 'circle') && !share.invitationSent),
	public: (state) => state.list.filter((share) => ['public'].includes(share.type)),
	hasShares: (state) => state.list.length > 0,
}

const actions = {
	async list(context) {
		try {
			const response = await SharesAPI.getShares(context.rootState.route.params.id)
			context.commit('set', response.data)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error loading shares', { error: e.response }, { pollId: context.rootState.route.params.id })
			throw e
		}
	},

	async add(context, payload) {
		try {
			await SharesAPI.addShare(context.rootState.route.params.id, payload.share)
			context.dispatch('list')
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error writing share', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async switchAdmin(context, payload) {
		const setTo = payload.share.type === 'user' ? 'admin' : 'user'

		try {
			await SharesAPI.switchAdmin(payload.share.token, setTo)
			context.dispatch('list')
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error(`Error switching type to ${setTo}`, { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async setPublicPollEmail(context, payload) {
		try {
			await SharesAPI.setEmailAddressConstraint(payload.share.token, payload.value)
			context.dispatch('list')
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error changing email register setting', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async writeLabel(context, payload) {
		try {
			await SharesAPI.writeLabel(payload.token, payload.displayName)
			// context.dispatch('list')
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error writing share label', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async inviteAll(context, payload) {
		try {
			const response = await SharesAPI.inviteAll(payload.pollId)
			context.dispatch('list')
			return response
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error sending invitation', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}

	},
	async sendInvitation(context, payload) {
		try {
			const response = await SharesAPI.sendInvitation(payload.share.token)
			context.dispatch('list')
			return response
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error sending invitation', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},

	async resolveGroup(context, payload) {

		try {
			await SharesAPI.resolveShare(payload.share.token)
			context.dispatch('list')
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error exploding group', e.response.data, { error: e.response }, { payload })
			throw e
		}
	},

	async delete(context, payload) {
		context.commit('delete', { share: payload.share })
		try {
			await SharesAPI.deleteShare(payload.share.token)
			context.dispatch('list')
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error removing share', { error: e.response }, { payload })
			context.dispatch('list')
			throw e
		}
	},
}

export default { namespaced, state, mutations, actions, getters }
