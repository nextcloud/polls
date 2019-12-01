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
		owner: undefined,
		acl: {}
	}
}

const state = defaultEvent()

const mutations = {
	setEvent(state, payload) {
		Object.assign(state, payload.event)
	},

	eventReset(state) {
		Object.assign(state, defaultEvent())
	},

	setEventProperty(state, payload) {
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
		if (state.acl.accessLevel === 'public') {
			return t('polls', 'Public access')
		} else if (state.acl.accessLevel === 'select') {
			return t('polls', 'Only shared')
		} else if (state.acl.accessLevel === 'registered') {
			return t('polls', 'Registered users only')
		} else if (state.acl.accessLevel === 'hidden') {
			return t('polls', 'Hidden poll')
		} else {
			return state.acl.accessLevel
		}
	},

	adminMode: state => {
		return (!state.acl.isOwner && state.acl.isAdmin)
	},

	allowEdit: (state, getters) => {
		return (state.acl.allowEdit)
	}

}

const actions = {

	loadEvent({ commit }, payload) {
		commit('eventReset')
		let endPoint = 'apps/polls/get/event/'

		if (payload.token !== undefined) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			return
		}

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				commit('setEvent', { 'event': response.data })
				// return response
			}, (error) => {
				if (error.response.status !== '404') {
					console.error('Error loading event', { 'error': error.response }, { 'payload': payload })
				}
				throw error
			})
	},

	addEventPromise({ commit }, payload) {
		return axios.post(OC.generateUrl('apps/polls/add/event'), { event: payload.event })
			.then((response) => {
				return response
			}, (error) => {
				console.error('Error adding event', { 'error': error.response }, { 'payload': payload })
				throw error
			})

	},

	deleteEventPromise({ commit }, payload) {
		return axios.post(OC.generateUrl('apps/polls/delete/event'), { event: payload.id })
			.then((response) => {
				return response
			}, (error) => {
				console.error('Error deleting event', { 'error': error.response }, { 'payload': payload })
				throw error
			})

	},

	writeEventPromise({ commit, rootState }) {
		return axios.post(OC.generateUrl('apps/polls/write/event'), { event: state, mode: rootState.poll.mode })
			.then((response) => {
				commit('setEvent', { 'event': response.data })
			}, (error) => {
				console.error('Error writing event:', { 'error': error.response }, { 'state': state })
				throw error
			})

	}
}

export default { state, mutations, getters, actions }
