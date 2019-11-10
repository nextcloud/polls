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
		id: 0,
		mode: 'create'
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
		Object.assign(state, payload)
	}
}

const getters = {
	currentUser: state => {
		return OC.getCurrentUser().uid
	}
}

const actions = {
	loadPoll({ commit, rootState }, payload) {
		commit('pollSetProperty', {'mode': payload.mode })
		commit('pollSetProperty', {'id': payload.pollId })

	}
}

export default {
	state,
	mutations,
	getters,
	actions
}
