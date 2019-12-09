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

const defaultShares = () => {
	return {
		list: []
	}
}

const state = defaultShares()

const mutations = {
	setShares(state, payload) {
		Object.assign(state, payload)
	},

	removeShare(state, payload) {
		state.list = state.list.filter(share => {
			return share.id !== payload.share.id
		})
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},

	addShare(state, payload) {
		state.list.push(payload)
	}

}

const getters = {
	sortedShares: state => {
		return state.list
	},

	invitationShares: state => {
		let invitationTypes = ['user', 'group', 'mail', 'external']
		return state.list.filter(function(share) {
			return invitationTypes.includes(share.type)
		})
	},

	publicShares: state => {
		let invitationTypes = ['public']
		return state.list.filter(function(share) {
			return invitationTypes.includes(share.type)
		})
	},

	countShares: state => {
		return state.list.length
	}
}

const actions = {
	loadPoll({ commit, rootState }, payload) {
		commit('reset')
		// console.log(rootState.acl)
		// if (!rootState.acl.allowEdit) {
		// 	console.log('rootState.acl.allowEdit', rootState.acl.allowEdit)
		// 	return
		// }
		// console.log('number 1')
		let endPoint = 'apps/polls/get/shares/'

		if (payload.token !== undefined) {
			return
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			return
		}

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				commit('setShares', {
					'list': response.data
				})
			}, (error) => {
				console.error('Error loading shares', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	},

	getShareAsync({ commit }, payload) {
		return axios.get(OC.generateUrl('apps/polls/get/share/' + payload.token))
			.then((response) => {
				return { 'share': response.data }
			}, (error) => {
				console.error('Error loading share', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	},

	addShareFromUser({ commit }, payload) {
		return axios.post(OC.generateUrl('apps/polls/write/share/s'), { token: payload.token, userName: payload.userName })
			.then((response) => {
				return { 'token': response.data.token }
			}, (error) => {
				console.error('Error writing share', { 'error': error.response }, { 'payload': payload })
				throw error
			})

	},

	writeSharePromise({ commit, rootState }, payload) {
		payload.share.pollId = rootState.event.id
		return axios.post(OC.generateUrl('apps/polls/write/share'), { pollId: rootState.event.id, share: payload.share })
			.then((response) => {
				commit('addShare', response.data)
			}, (error) => {
				console.error('Error writing share', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	},

	removeShareAsync({ commit, getters, dispatch, rootState }, payload) {
		return axios.post(OC.generateUrl('apps/polls/remove/share'), { share: payload.share })
			.then((response) => {
				commit('removeShare', { 'share': payload.share })
			}, (error) => {
				console.error('Error removing share', { 'error': error.response }, { 'payload': payload })
				throw error
			})
	}

}

export default { state, mutations, actions, getters }
