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
import moment from '@nextcloud/moment'
import { generateUrl } from '@nextcloud/router'
import acl from './subModules/acl.js'
import comments from './subModules/comments.js'

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
		allowMaybe: 0,
		voteLimit: 0,
		showResults: 'always',
		adminAccess: 0,
		settings: '',
		options: '',
	}
}

const state = defaultPoll()

const namespaced = true
const modules = {
	acl: acl,
	comments: comments,
}
const mutations = {
	set(state, payload) {
		Object.assign(state, payload.poll)
	},

	reset(state) {
		Object.assign(state, defaultPoll())
	},

	setProperty(state, payload) {
		Object.assign(state, payload)
	},

}

const getters = {

	expired: (state) => {
		return (state.expire > 0 && moment.unix(state.expire).diff() < 0)
	},

	// accessType: (state) => {
	// 	if (state.access === 'public') {
	// 		return t('polls', 'Public access')
	// 	} else if (state.access === 'hidden') {
	// 		return t('polls', 'Hidden poll')
	// 	} else {
	// 		return state.access
	// 	}
	// },

	// allowEdit: (state, getters, rootState) => {
	// 	return (rootState.poll.acl.allowEdit)
	// },

}

const actions = {

	reset(context) {
		context.commit('reset')
	},

	load(context, payload) {
		let endPoint = 'apps/polls/polls/get/'
		if (payload.token) {
			endPoint = endPoint.concat('s/', payload.token)
		} else if (payload.pollId) {
			endPoint = endPoint.concat(payload.pollId)
		} else {
			context.commit('reset')
			context.commit('acl/reset')
			context.commit('comments/reset')
			return
		}
		return axios.get(generateUrl(endPoint))
			.then((response) => {
				context.commit('set', response.data)
				context.commit('acl/set', response.data)
				context.commit('comments/set', response.data)
				// context.commit('options/set', response.data)
				// context.commit('shares/set', response.data)
				// context.commit('votes/set', response.data)
				return response
			}, (error) => {
				if (error.response.status !== '404' && error.response.status !== '401') {
					console.debug('Error loading poll', { error: error.response }, { payload: payload })
					return error.response
				}
				throw error
			})
	},

	write(context) {
		const endPoint = 'apps/polls/polls/write/'
		return axios.post(generateUrl(endPoint), { poll: state })
			.then((response) => {
				context.commit('set', response.data)
				return response.data.poll
			}, (error) => {
				console.error('Error writing poll:', { error: error.response }, { state: state })
				throw error
			})

	},
}

export default { namespaced, state, mutations, getters, actions, modules }
