/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { CalendarAPI, UserSettingsAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultSettings = () => ({
	user: {
		useCommentsAlternativeStyling: false,
		useAlternativeStyling: false,
		calendarPeek: false,
		checkCalendars: [],
		checkCalendarsBefore: 0,
		checkCalendarsAfter: 0,
		defaultViewTextPoll: 'table-view',
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
			context.commit('setPreference', response.data.preferences)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			context.commit('reset')
			throw error
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
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing preferences', { error }, { preferences: state.user })
			throw error
		}
	},

	async getCalendars(context) {
		try {
			const response = await CalendarAPI.getCalendars()
			context.commit('setCalendars', { calendars: response.data.calendars })
			return response
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			throw error
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
