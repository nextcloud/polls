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
		list: [],
	}
}

const state = defaultShares()

const namespaced = true

const mutations = {
	set(state, payload) {
		state.list = payload.shares
	},

	delete(state, payload) {
		state.list = state.list.filter(share => {
			return share.id !== payload.share.id
		})
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},

	add(state, payload) {
		state.list.push(payload)
	},

	update(state, payload) {
		const foundIndex = state.list.findIndex(share => share.id === payload.share.id)
		Object.assign(state.list[foundIndex], payload.share)
	},

}

const getters = {
	invitation: state => {
		// share types, which will be active, after the user gets his invitation
		const invitationTypes = ['email', 'external', 'contact']
		// sharetype which are active without sending an invitation
		const directShareTypes = ['user', 'group']
		return state.list.filter(share => {
			return (invitationTypes.includes(share.type) && share.invitationSent) || directShareTypes.includes(share.type)
		})
	},

	unsentInvitations: state => {
		return state.list.filter(share => {
			return (share.userEmail || share.type === 'group' || share.type === 'contactGroup' || share.type === 'circle') && !share.invitationSent
		})
	},

	public: state => {
		const invitationTypes = ['public']
		return state.list.filter(share => {
			return invitationTypes.includes(share.type)
		})
	},

}

const actions = {
	add(context, payload) {
		const endPoint = 'apps/polls/share/add'
		return axios.post(generateUrl(endPoint), {
			pollId: context.rootState.poll.id,
			type: payload.type,
			userId: payload.userId,
			emailAddress: payload.userEmail,
		})
			.then((response) => {
				context.commit('add', response.data.share)
				return response.data
			})
			.catch((error) => {
				console.error('Error writing share', { error: error.response }, { payload: payload })
				throw error
			})
	},

	addPersonal(context, payload) {
		const endPoint = 'apps/polls/share/personal'

		return axios.post(generateUrl(endPoint), { token: payload.token, userName: payload.userName, emailAddress: payload.emailAddress })
			.then((response) => {
				return { token: response.data.token }
			})
			.catch((error) => {
				console.error('Error writing personal share', { error: error.response }, { payload: payload })
				throw error
			})

	},

	delete(context, payload) {
		const endPoint = 'apps/polls/share/delete'
		return axios.delete(generateUrl(endPoint.concat('/', payload.share.token)))
			.then(() => {
				context.commit('delete', { share: payload.share })
			})
			.catch((error) => {
				console.error('Error removing share', { error: error.response }, { payload: payload })
				throw error
			})
	},

	sendInvitation(context, payload) {
		const endPoint = 'apps/polls/share/send'
		return axios.post(generateUrl(endPoint.concat('/', payload.share.token)))
			.then((response) => {
				context.commit('update', { share: response.data.share })
				return response
			})
			.catch((error) => {
				console.error('Error sending invitation', { error: error.response }, { payload: payload })
				throw error
			})
	},

	resolveGroup(context, payload) {
		const endPoint = 'apps/polls/share/resolveGroup'
		return axios.get(generateUrl(endPoint.concat('/', payload.share.token)))
			.then((response) => {
				response.data.shares.forEach((item) => {
					context.commit('add', item)
				})
				return response
			})
			.catch((error) => {
				console.error('Error exploding group', { error: error.response }, { payload: payload })
				throw error
			})
	},
}

export default { namespaced, state, mutations, actions, getters }
