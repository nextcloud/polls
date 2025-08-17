/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

import { CalendarAPI, UserSettingsAPI } from '../Api'
import { Logger } from '../helpers'

import type { AxiosError } from '@nextcloud/axios'
import type { PreferencesStore, Calendar } from './preferences.types'

export const usePreferencesStore = defineStore('preferences', {
	state: (): PreferencesStore => ({
		user: {
			calendarPeek: false,
			checkCalendars: [],
			checkCalendarsHoursBefore: 0,
			checkCalendarsHoursAfter: 0,
			defaultViewTextPoll: 'table-view',
			defaultViewDatePoll: 'table-view',
			pollCombo: [],
			relevantOffset: 30,
			useNewPollDialogInNavigation: false,
			useNewPollInPollist: false,
			useCommentsAlternativeStyling: false,
			useAlternativeStyling: false,
			verbosePollsList: false,
			variantCreation: false,
		},
		availableCalendars: [],
	}),

	getters: {
		useNcAppNavigationNew(state): boolean {
			return (
				!state.user.useNewPollDialogInNavigation
				&& !state.user.useNewPollInPollist
			)
		},

		useActionAddPollInNavigation(state): boolean {
			return (
				state.user.useNewPollDialogInNavigation
				&& !state.user.useNewPollInPollist
			)
		},
	},

	actions: {
		setCalendars(payload: { calendars: Calendar[] }) {
			this.availableCalendars = payload.calendars
		},

		addCheckCalendar(calendar: Calendar) {
			this.user.checkCalendars.push(calendar.key)
			this.write()
		},

		removeCheckCalendar(calendar: Calendar) {
			const index = this.user.checkCalendars.indexOf(calendar.key)
			if (index !== -1) {
				this.user.checkCalendars.splice(index, 1)
			}
			this.write()
		},

		async load(): Promise<void> {
			try {
				const response = await UserSettingsAPI.getUserSettings()
				this.$patch({ user: response.data.preferences })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.$reset()
				throw error
			}
		},

		async write(): Promise<void> {
			try {
				const response = await UserSettingsAPI.writeUserSettings(this.user)
				this.$patch({ user: response.data.preferences })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing preferences', {
					error,
					preferences: this.user,
				})
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
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				throw error
			}
		},
	},
})
