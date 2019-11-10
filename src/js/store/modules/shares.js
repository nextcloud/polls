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

import axios from 'nextcloud-axios'

const defaultComments = () => {
	return {
		list: []
	}
}

const state = defaultComments()

const mutations = {
	sharesSet(state, payload) {
		Object.assign(state, payload)
	},

	shareRemove(state, payload) {
		state.list = state.list.filter(share => {
			return share.id !== payload.share.id
		})
	},

	sharesReset(state) {
		Object.assign(state, defaultComments())
	},

	shareAdd(state, payload) {
		state.list.push(payload)
	}

}

const getters = {
	sortedShares: state => {
		return state.list
	},

	invitationShares: state => {
		var invitationTypes = ['user', 'group']
		return state.list.filter(function(share) {
			return invitationTypes.includes(share.type)
		})
	},

	publicShares: state => {
		var invitationTypes = ['public']
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
		commit({ type: 'sharesReset' })
		axios
			.get(OC.generateUrl('apps/polls/get/shares/' + payload.pollId))
			.then((response) => {
				commit('sharesSet', {
					'list': response.data
				})
			}, (error) => {
				commit({ type: 'sharesReset' })
				console.error(error)
			})
	},

	writeSharePromise({ commit, rootState }, payload) {
		payload.share.pollId = rootState.event.id
		return axios
			.post(OC.generateUrl('apps/polls/write/share'), { pollId: rootState.event.id, share: payload.share })
			.then((response) => {
				commit('shareAdd', response.data)
			}, (error) => {
				console.error('writeSharePromise error', error)
			})
	},

	removeShareAsync({ commit, getters, dispatch, rootState }, payload) {
		return axios.post(OC.generateUrl('apps/polls/remove/share'), { share: payload.share })
			.then((response) => {
				commit('shareRemove', { 'share': payload.share })
			}, (error) => {
				console.error(error)
			})
	}

}

export default { state, mutations, actions, getters }
