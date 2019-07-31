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
import sortBy from 'lodash/sortBy'
import moment from 'moment'

const defaultEvent = () => {
	return {
		id: 0,
		hash: '',
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
		state[payload.property] = payload.value
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
				/* eslint-disable-next-line no-console */
					console.log(error)
				})
		}
	},

	writeEventPromise({ commit }, payload) {
		return
		return axios.post(OC.generateUrl('apps/polls/write/event'), { event: state.event, mode: payload.mode })
		.then((response) => {
			commit('eventSet', { 'event': response.data })
		}, (error) => {
			state.hash = ''
			/* eslint-disable-next-line no-console */
			console.log(error.response)
		})

	}
}

export default { state, mutations, getters, actions }
