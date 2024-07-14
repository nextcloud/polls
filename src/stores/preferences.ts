/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { CalendarAPI, UserSettingsAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'

export enum ViewMode {
	TableView = 'table-view',
	ListView = 'list-view',
}

interface UserPreferences {
	useCommentsAlternativeStyling: boolean
	useAlternativeStyling: boolean
	calendarPeek: boolean
	checkCalendars: [],
	checkCalendarsHoursBefore: number,
	checkCalendarsHoursAfter: number,
	defaultViewTextPoll: ViewMode
	defaultViewDatePoll: ViewMode
	performanceThreshold: number,
	pollCombo: number[],
	relevantOffset: number,
}

export interface SessionSettings {
	manualViewDatePoll: '' | ViewMode
	manualViewTextPoll: '' | ViewMode
}

export interface Calendar {
	key: string
	name: string
	calendarUri: string
	displayColor: string
	permissions: number
}

export interface Preferences {
	user: UserPreferences
	session: SessionSettings,
	availableCalendars: Calendar[],
	viewModes: ViewMode[],
}

export const usePreferencesStore = defineStore('preferences', {
	state: (): Preferences => ({
		user: {
			useCommentsAlternativeStyling: false,
			useAlternativeStyling: false,
			calendarPeek: false,
			checkCalendars: [],
			checkCalendarsHoursBefore: 0,
			checkCalendarsHoursAfter: 0,
			defaultViewTextPoll: ViewMode.TableView,
			defaultViewDatePoll: ViewMode.TableView,
			performanceThreshold: 1000,
			pollCombo: [],
			relevantOffset: 30,
		},
		session: {
			manualViewDatePoll: '',
			manualViewTextPoll: '',
		},
		availableCalendars: [],
		viewModes: Object.values(ViewMode),
	}),

	getters: {
		viewTextPoll(state): ViewMode {
			if (state.session.manualViewTextPoll) {
				return state.session.manualViewTextPoll
			}
			if (window.innerWidth > 480) {
				return state.user.defaultViewTextPoll
			}
			return ViewMode.ListView
		},

		viewDatePoll(state): ViewMode {
			if (state.session.manualViewDatePoll) {
				return state.session.manualViewDatePoll
			}
			if (window.innerWidth > 480) {
				return state.user.defaultViewDatePoll
			}
			return ViewMode.ListView
	
		},
	},

	actions: {
		writePreference(payload: { key: string, value: boolean|number|string|Array<string> }) {
			this.$patch(payload)
			this.write()
		},

		setCalendars(payload) {
			this.availableCalendars = payload.calendars
		},
	
		addCheckCalendar(calendar: Calendar ) {
			this.user.checkCalendars.push(calendar.key)
			this.write()
		},
	
		removeCheckCalendar(calendar: Calendar) {
			const index = this.user.checkCalendars.indexOf(calendar.key);
			if (index !== -1) {
				this.user.checkCalendars.splice(index, 1);
			}
			this.write()
		},
	
		setViewDatePoll(viewMode: ViewMode) {
			this.session.manualViewDatePoll = viewMode
		},

		setViewTextPoll(viewMode: ViewMode) {
			this.session.manualViewTextPoll = viewMode
		},
	
		async load(): Promise<void> {
			try {
				const response = await UserSettingsAPI.getUserSettings()
				this.$patch({ user: response.data.preferences })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
				throw error
			}
		},

		async setPollCombo(payload: { pollCombo: Array<string> }): Promise<void> {
			this.user.pollCombo = payload.pollCombo
			this.write()
		},

		async write(): Promise<void> {
			try {
				const response = await UserSettingsAPI.writeUserSettings(this.user)
				this.$patch({ user: response.data.preferences })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing preferences', { error, preferences: this.user })
				throw error
			}
		},
	
		async getCalendars() {
			try {
				const response = await CalendarAPI.getCalendars()
				// this.availableCalendars = response.data.calendars
				this.setCalendars({ calendars: response.data.calendars })
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				throw error
			}
		},
	},
})
