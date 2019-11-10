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
import moment from 'moment'

const defaultEvent = () => {
	return {
		id: 0,
		type: 'datePoll',
		title: '',
		description: '',
		created: '',
		access: 'public',
		expiration: false,
		expirationDate: '',
		expired: false,
		isAnonymous: false,
		fullAnonymous: false,
		allowMaybe: false,
		owner: undefined
	}
}

const state = defaultEvent()

const mutations = {
	eventSet(state, payload) {
		Object.assign(state, payload.event)
	},

	eventReset(state) {
		Object.assign(state, defaultEvent())
	},

	eventSetProperty(state, payload) {
		Object.assign(state, payload)
	}

}

const getters = {

	timeSpanCreated: state => {
		return moment(state.created).fromNow()
	},

	timeSpanExpiration: state => {
		if (state.expiration) {
			return moment(state.expirationDate).fromNow()
		} else {
			return t('polls', 'never')
		}
	},

	accessType: state => {
		if (state.access === 'public') {
			return t('polls', 'Public access')
		} else if (state.access === 'select') {
			return t('polls', 'Only shared')
		} else if (state.access === 'registered') {
			return t('polls', 'Registered users only')
		} else if (state.access === 'hidden') {
			return t('polls', 'Hidden poll')
		} else {
			return ''
		}
	},

	adminMode: state => {
		return (state.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
	},

	allowEdit: (state, getters) => {
		return (state.owner === OC.getCurrentUser().uid || getters.adminMode)
	}

}

const actions = {

	loadEvent({ commit }, payload) {
		commit({ type: 'eventReset' })
		if (payload.mode !== 'create') {
			return axios.get(OC.generateUrl('apps/polls/get/event/' + payload.pollId))
				.then((response) => {
					commit('eventSet', { 'event': response.data })
				}, (error) => {
					console.error('writeEventPromise - error:', error)
				})
		}
	},

	addEventPromise({ commit }, payload) {
		return axios.post(OC.generateUrl('apps/polls/add/event'), { event: payload.event })
			.then((response) => {
				return response
			}, (error) => {
				console.error('addEventPromise - error:', error.response)
			})

	},

	deleteEventPromise({ commit }, payload) {
		return axios.post(OC.generateUrl('apps/polls/delete/event'), { event: payload.id })
			.then(() => {
			}, (error) => {
				console.error('deleteEventPromise - error:', error.response)
			})

	},

	writeEventPromise({ commit, rootState }) {
		return axios.post(OC.generateUrl('apps/polls/write/event'), { event: state, mode: rootState.poll.mode })
			.then((response) => {
				commit('eventSet', { 'event': response.data })
			}, (error) => {
				console.error('writeEventPromise - error:', error.response)
			})

	}
}

export default { state, mutations, getters, actions }
