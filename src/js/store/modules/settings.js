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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const defaultSettings = () => ({
	user: {
		useDashboardStyling: false,
		useIndividualStyling: false,
		useCommentsAlternativeStyling: false,
		individualBgColor: false,
		individualImage: false,
		individualImageUrl: '',
		individualImageStyle: 'light',
		translucentPanels: false,
		calendarPeek: false,
		checkCalendars: [],
		checkCalendarsBefore: 0,
		checkCalendarsAfter: 0,
		defaultViewTextPoll: 'list-view',
		defaultViewDatePoll: 'table-view',
		performanceThreshold: 1000,
		pollCombo: [],
	},
	session: {
		manualViewDatePoll: '',
		manualViewTextPoll: '',
	},
	dashboard: {
		background: '',
		themingDefaultBackground: '',
		backgroundVersion: 0,
		shippedBackgrounds: '',
		isInstalled: false,
		theming: 'light',
	},
	availableCalendars: [],
	viewModes: [
		'list-view',
		'table-view',
	],
})

const state = defaultSettings()
const namespaced = true

const mutations = {
	reset(state) {
		Object.assign(state, defaultSettings())
	},

	setPreference(state, payload) {

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

	setDashboard(state, payload) {
		state.dashboard = payload
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
	themeClass(state) {
		if (state.dashboard.isInstalled && state.user.useDashboardStyling) {
			return `dashboard--${state.dashboard.theming}`
		}
		if (state.user.useIndividualStyling && state.user.individualImage) {
			return `polls--${state.user.individualImageStyle}`
		}
		return ''
	},

	backgroundClass(state) {
		if (state.user.useDashboardStyling) {
			return ''
		}

		if (state.user.useIndividualStyling && state.user.individualImage) {
			return 'polls--bg-image'
		}

		if (state.user.useIndividualStyling && state.user.individualBgColor) {
			return 'polls--bg-color'
		}

		return ''
	},
	useDashboardStyling(state) {
		return state.dashboard.isInstalled && state.user.useDashboardStyling
	},

	useIndividualStyling(state) {
		return !state.user.useDashboardStyling && state.user.useIndividualStyling
	},

	useTranslucentPanels(state) {
		return (state.dashboard.isInstalled && state.user.useDashboardStyling)
			|| (state.user.useIndividualStyling && state.user.translucentPanels)
	},

	appBackground(state) {
		const imageProps = 'no-repeat fixed center center / cover'
		if (state.dashboard.isInstalled && state.user.useDashboardStyling) {
			if (state.dashboard.background === 'custom') {
				return `url("${generateUrl('/apps/dashboard/background')}?v=${window.OCA.Theming.cacheBuster}") ${imageProps}`
			}

			if (!state.dashboard.background) {
				return `url("${generateUrl('/apps/theming/image/background')}?v=${window.OCA.Theming.cacheBuster}") ${imageProps}`
			}

			if (state.dashboard.background.charAt(0) === '#') {
				return state.dashboard.background
			}

			return `url("${generateUrl('/apps/dashboard/img/')}${state.dashboard.background}") ${imageProps}`
		}

		if (state.user.useIndividualStyling) {
			if (state.user.individualImage) {
				return `url("${state.user.individualImageUrl}") ${imageProps}`
			}
			if (state.user.individualBgColor) {
				return 'var(--color-primary-light)'
			}
		}
		return 'var(--color-main-background)'
	},

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
		const endPoint = 'apps/polls/preferences'
		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
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
			context.commit('setDashboard', response.data.dashboard)
		} catch {
			context.commit('reset')
		}
	},

	async setPollCombo(context, payload) {
		await context.commit('setPollCombo', {
			headers: { Accept: 'application/json' },
			pollCombo: payload.pollCombo,
		})
		context.dispatch('write')
	},

	async write(context) {
		const endPoint = 'apps/polls/preferences'
		try {
			const response = await axios.post(generateUrl(endPoint), {
				headers: { Accept: 'application/json' },
				settings: context.state.user,
			})
			context.commit('setPreference', response.data.preferences)
		} catch (e) {
			console.error('Error writing preferences', { error: e.response }, { preferences: state.user })
			throw e
		}
	},

	async getCalendars(context) {
		const endPoint = 'apps/polls/calendars'
		const response = await axios.get(generateUrl(endPoint), {
			headers: { Accept: 'application/json' },
		})
		context.commit('setCalendars', { calendars: response.data.calendars })
		return response
	},
}

export default { namespaced, state, mutations, getters, actions }
