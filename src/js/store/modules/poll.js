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

const defaultPoll = () => {
	return {
		id: 0,
		type: 'datePoll',
		title: '',
		description: '',
		owner: undefined,
		created: '',
		access: 'public',
		expire: null,
		expiration: false,
		isAnonymous: false,
		fullAnonymous: false,
		allowMaybe: false,
		voteLimit: null,
		showResults: true,
		deleted: false,
		deleteDate: null
	}
}

const state = defaultPoll()

const mutations = {
	setPoll(state, payload) {
		Object.assign(state, payload.poll)
	},

	resetPoll(state) {
		Object.assign(state, defaultPoll())
	},

	setPollProperty(state, payload) {
		Object.assign(state, payload)
	}

}

const getters = {

	expired: (state, getters) => {
		return (state.expiration && moment(state.expire).diff() < 0)
	},

	accessType: (state, getters, rootState) => {
		if (rootState.acl.accessLevel === 'public') {
			return t('polls', 'Public access')
		} else if (rootState.acl.accessLevel === 'select') {
			return t('polls', 'Only shared')
		} else if (rootState.acl.accessLevel === 'registered') {
			return t('polls', 'Registered users only')
		} else if (rootState.acl.accessLevel === 'hidden') {
			return t('polls', 'Hidden poll')
		} else {
			return rootState.acl.accessLevel
		}
	},

	allowEdit: (state, getters, rootState) => {
		return (rootState.acl.allowEdit)
	}

}

const actions = {

	loadPoll({ commit }, payload) {
		let endPoint = 'apps/polls/poll/get/'

		if (payload.token !== undefined) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId !== undefined) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			return
		}

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				commit('setPoll', { 'poll': response.data })
			}, (error) => {
				if (error.response.status !== '404') {
					console.error('Error loading poll', { 'error': error.response }, { 'payload': payload })
				}
				throw error
			})
	},

	deletePollPromise({ commit }, payload) {
		let endPoint = 'apps/polls/poll/delete/'

		return axios.post(OC.generateUrl(endPoint), { poll: payload.id })
			.then((response) => {
				return response
			}, (error) => {
				console.error('Error deleting poll', { 'error': error.response }, { 'payload': payload })
				throw error
			})

	},

	writePollPromise({ commit, rootState }) {
		let endPoint = 'apps/polls/poll/write/'

		return axios.post(OC.generateUrl(endPoint), { poll: state })
			.then((response) => {
				commit('setPoll', { 'poll': response.data })
				return response.poll
			}, (error) => {
				console.error('Error writing poll:', { 'error': error.response }, { 'state': state })
				throw error
			})

	}
}

export default { state, mutations, getters, actions, defaultPoll }
