/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2020 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
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

import { CalendarAPI } from '../../Api/calendar.js'
import { UserSettingsAPI } from '../../Api/userSettings.js'

const defaultSettings = () => ({
	user: {
		useCommentsAlternativeStyling: false,
		calendarPeek: false,
		checkCalendars: [],
		checkCalendarsBefore: 0,
		checkCalendarsAfter: 0,
		defaultViewTextPoll: 'list-view',
		defaultViewDatePoll: 'table-view',
		performanceThreshold: 1000,
		pollCombo: [],
		relevantOffset: 30,
	},
	session: {
		manualViewDatePoll: '',
		manualViewTextPoll: '',
	},
	availableCalendars: [],
	viewModes: [
		'list-view',
		'table-view',
	],
})

const namespaced = true
const state = defaultSettings()

const mutations = {
	reset(state) {
		Object.assign(state, defaultSettings())
	},

	setPreference(state, payload) {
		// change values in case of old settings
		if (payload.defaultViewTextPoll === 'desktop') {
			payload.defaultViewTextPoll = 'table-view'
		}
		if (payload.defaultViewTextPoll === 'mobile') {
			payload.defaultViewTextPoll = 'list-view'
		}
		if (payload.defaultViewDatePoll === 'desktop') {
			payload.defaultViewDatePoll = 'table-view'
		}
		if (payload.defaultViewDatePoll === 'mobile') {
			payload.defaultViewDatePoll = 'list-view'
		}

		Object.keys(payload).filter((key) => key in state.user).forEach((key) => {
			state.user[key] = payload[key]
		})
	},

	setCalendars(state, payload) {
		state.availableCalendars = payload.calendars
	},

	addCheckCalendar(state, payload) {
		state.user.checkCalendars.push(payload.calendar.key)
	},

	setPollCombo(state, payload) {
		state.user.pollCombo = payload.pollCombo
	},

	setViewDatePoll(state, payload) {
		state.session.manualViewDatePoll = payload
	},
	setViewTextPoll(state, payload) {
		state.session.manualViewTextPoll = payload
	},
}

const getters = {
	viewTextPoll(state) {
		if (state.session.manualViewTextPoll) {
			return state.session.manualViewTextPoll
		}
		if (window.innerWidth > 480) {
			return state.user.defaultViewTextPoll
		}
		return 'list-view'

	},

	viewDatePoll(state) {
		if (state.session.manualViewDatePoll) {
			return state.session.manualViewDatePoll
		}
		if (window.innerWidth > 480) {
			return state.user.defaultViewDatePoll
		}
		return 'list-view'

	},
}

const actions = {
	async get(context) {
		try {
			const response = await UserSettingsAPI.getUserSettings()
			if (response.data.preferences.defaultViewTextPoll === 'desktop') {
				response.data.preferences.defaultViewTextPoll = 'table-view'
			}
			if (response.data.preferences.defaultViewTextPoll === 'mobile') {
				response.data.preferences.defaultViewTextPoll = 'list-view'
			}
			if (response.data.preferences.defaultViewDatePoll === 'desktop') {
				response.data.preferences.defaultViewDatePoll = 'table-view'
			}
			if (response.data.preferences.defaultViewDatePoll === 'mobile') {
				response.data.preferences.defaultViewDatePoll = 'list-view'
			}
			context.commit('setPreference', response.data.preferences)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			context.commit('reset')
			throw e
		}
	},

	async setPollCombo(context, payload) {
		await context.commit('setPollCombo', {
			pollCombo: payload.pollCombo,
		})
		context.dispatch('write')
	},

	async write(context) {
		try {
			const response = await UserSettingsAPI.writeUserSettings(context.state.user)
			context.commit('setPreference', response.data.preferences)
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error writing preferences', { error: e.response }, { preferences: state.user })
			throw e
		}
	},

	async getCalendars(context) {
		try {
			const response = await CalendarAPI.getCalendars()
			context.commit('setCalendars', { calendars: response.data.calendars })
			return response
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			throw e
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
