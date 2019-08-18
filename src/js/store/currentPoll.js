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

const defaultPoll = () => {
	return {
		// votechanged: false,
		grantedAs: 'owner',
		id: 0,
		mode: 'create',
		result: 'new',
		shares: [],
		currentUser: ''
	}
}

const state = defaultPoll()

const mutations = {
	pollSet(state, payload) {
		Object.assign(state, payload.poll)
	},

	pollReset(state) {
		Object.assign(state, defaultPoll())
	},

	pollSetProperty(state, payload) {
		state[payload.property] = payload.value
	}
}

const actions = {
	addShare({
		commit
	}, payload) {
		// 	this.poll.shares.push(item)
	},

	updateShares({ commit }, payload) {
		// 	this.poll.shares = share.slice(0)
	},

	removeShare({ commit }, payload) {
		// 	this.shares.splice(this.shares.indexOf(item), 1)
	},

	loadPoll({ commit, rootState }, payload) {
		commit('pollSetProperty', {
			'property': 'mode',
			'value': payload.mode
		})
		commit('pollSetProperty', {
			'property': 'currentUser',
			'value': OC.getCurrentUser().uid
		})
		commit('pollSetProperty', {
			'property': 'grantedAs',
			'value': 'owner'
		})
		commit('pollSetProperty', {
			'property': 'id',
			'value': rootState.event.id
		})
		commit('pollSetProperty', {
			'property': 'result',
			'value': 'foundById'
		})

	}

	// writePollPromise({ commit }) {
	// 	return
	// 	if (state.mode !== 'vote') {
	//
	// 		return axios.post(OC.generateUrl('apps/polls/write/poll'), {
	// 				event: state.event,
	// 				voteOptions: state.voteOptions,
	// 				shares: state.shares,
	// 				mode: state.mode
	// 			})
	// 			.then((response) => {
	// 				commit('pollSetProperty', {
	// 					'property': 'mode',
	// 					'value': 'edit'
	// 				})
	// 				commit('pollSetProperty', {
	// 					'property': 'id',
	// 					'value': response.data.id
	// 				})
	// 				// window.location.href = OC.generateUrl('apps/polls/edit/' + this.event.hash)
	// 			}, (error) => {
	// 				console.error(error.response)
	// 			})
	//
	// 	}
	// }
}

export default {
	state,
	mutations,
	actions
}
