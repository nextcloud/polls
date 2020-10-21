/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
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
import { generateUrl } from '@nextcloud/router'

const defaultSettings = () => {
	return {
		user: {
			experimental: false,
			calendarPeek: false,
			checkCalendars: [],
			useImage: false,
			imageUrl: '',
			glassyNavigation: false,
			glassySidebar: false,
			defaultViewTextPoll: 'mobile',
			defaultViewDatePoll: 'desktop',
		},
		availableCalendars: [],
		viewModes: [
			'mobile',
			'desktop',
		],
	}
}

const state = defaultSettings()
const namespaced = true

const mutations = {
	reset(state) {
		Object.assign(state, defaultSettings())
	},

	setPreference(state, payload) {
		Object.keys(payload).filter(key => key in state.user).forEach(key => {
			state.user[key] = payload[key]
		})
	},
	setCalendars(state, payload) {
		state.availableCalendars = payload.calendars
	},
	addCheckCalendar(state, payload) {
		state.user.checkCalendars.push(payload.calendar.key)
	},
}

const actions = {
	get(context) {
		const endPoint = 'apps/polls/preferences/get'

		return axios.get(generateUrl(endPoint))
			.then((response) => {
				context.commit('setPreference', response.data.preferences)
			})
			.catch(() => {
				context.commit('reset')
			})
	},
	write(context) {
		const endPoint = 'apps/polls/preferences/write'

		return axios.post(generateUrl(endPoint), { settings: context.state.user })
			.then((response) => {
				context.commit('setPreference', response.data.preferences)
			})
			.catch((error) => {
				console.error('Error writing preferences', { error: error.response }, { preferences: state.user })
				throw error
			})
	},

	getCalendars(context) {
		const endPoint = 'apps/polls/calendars'

		return axios.get(generateUrl(endPoint))
			.then((response) => {
				context.commit('setCalendars', { calendars: response.data.calendars })
				return response
			})
	},
}

export default { namespaced, state, mutations, actions }
