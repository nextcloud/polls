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
			return (invitationTypes.includes(share.type) && (share.type === 'external' || share.invitationSent)) || directShareTypes.includes(share.type)
		})
	},

	unsentInvitations: state => {
		return state.list.filter(share => {
			return (share.emailAddress || share.type === 'group' || share.type === 'contactGroup' || share.type === 'circle') && !share.invitationSent
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
	list(context) {
		const endPoint = 'apps/polls/poll/' + context.rootState.poll.id
		return axios.get(generateUrl(endPoint + '/shares'))
			.then((response) => {
				context.commit('set', response.data)
				return response.data
			})
			.catch((error) => {
				console.error('Error loading shares', { error: error.response }, { pollId: context.rootState.poll.id })
				throw error
			})
	},

	add(context, payload) {
		const endPoint = 'apps/polls/poll/' + context.rootState.poll.id

		return axios.post(generateUrl(endPoint + '/share'), payload.share)
			.then((response) => {
				return response.data
			})
			.catch((error) => {
				console.error('Error writing share', { error: error.response }, { payload: payload })
				throw error
			})
			.finally(() => {
				context.dispatch('list')
			})
	},

	addPersonal(context, payload) {
		const endPoint = 'apps/polls/share/personal'
		return axios.post(generateUrl(endPoint), { token: payload.token, userName: payload.userName, emailAddress: payload.emailAddress })
			.then((response) => {
				return { token: response.data.share.token }
			})
			.catch((error) => {
				console.error('Error writing personal share', { error: error.response }, { payload: payload })
				throw error
			})

	},

	delete(context, payload) {
		const endPoint = 'apps/polls/share'
		context.commit('delete', { share: payload.share })

		return axios.delete(generateUrl(endPoint + '/' + payload.share.token))
			.then((response) => {
				return response
			})
			.catch((error) => {
				console.error('Error removing share', { error: error.response }, { payload: payload })
				throw error
			})
			.finally(() => {
				context.dispatch('list')
			})
	},

	sendInvitation(context, payload) {
		const endPoint = 'apps/polls/share/send'

		return axios.post(generateUrl(endPoint + '/' + payload.share.token + '/send'))
			.then((response) => {
				return response
			})
			.catch((error) => {
				console.error('Error sending invitation', { error: error.response }, { payload: payload })
				throw error
			})
			.finally(() => {
				context.dispatch('list')
			})
	},

	resolveGroup(context, payload) {
		const endPoint = 'apps/polls/share'

		return axios.get(generateUrl(endPoint + '/' + payload.share.token + '/resolve'))
			.catch((error) => {
				console.error('Error exploding group', error.response.data, { error: error.response }, { payload: payload })
				throw error
			})
			.finally(() => {
				context.dispatch('list')
			})
	},
}

export default { namespaced, state, mutations, actions, getters }
