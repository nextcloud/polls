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
		owner: '',
		created: 0,
		expire: 0,
		deleted: 0,
		access: 'hidden',
		anonymous: 0,
		fullAnonymous: 0,
		allowMaybe: 0,
		voteLimit: 0,
		showResults: 'always',
		adminAccess: 0,
		settings: '',
		options: ''
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

	expired: (state) => {
		return (state.expire > 0 && moment.unix(state.expire).diff() < 0)
	},

	accessType: (state, getters, rootState) => {
		if (rootState.acl.accessLevel === 'public') {
			return t('polls', 'Public access')
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

	loadPollMain(context, payload) {
		let endPoint = 'apps/polls/poll/get/'

		if (payload.token) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			context.commit('resetPoll')
			return
		}
		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				context.commit('setPoll', { poll: response.data })
			}, (error) => {
				if (error.response.status !== '404') {
					console.error('Error loading poll', { error: error.response }, { payload: payload })
				}
				throw error
			})
	},

	deletePollPromise(context, payload) {
		const endPoint = 'apps/polls/poll/delete/'

		return axios.post(OC.generateUrl(endPoint), { poll: payload.id })
			.then((response) => {
				return response
			}, (error) => {
				console.error('Error deleting poll', { error: error.response }, { payload: payload })
				throw error
			})

	},

	writePollPromise(context) {
		const endPoint = 'apps/polls/poll/write/'
		return axios.post(OC.generateUrl(endPoint), { poll: state })
			.then((response) => {
				context.commit('setPoll', { poll: response.data })
				return response.poll
			}, (error) => {
				console.error('Error writing poll:', { error: error.response }, { state: state })
				throw error
			})

	}
}

export default { state, mutations, getters, actions, defaultPoll }
