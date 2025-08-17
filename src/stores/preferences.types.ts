/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type ViewMode = 'table-view' | 'list-view'

export type UserPreferences = {
	calendarPeek: boolean
	checkCalendars: string[]
	checkCalendarsHoursBefore: number
	checkCalendarsHoursAfter: number
	defaultViewTextPoll: ViewMode
	defaultViewDatePoll: ViewMode
	pollCombo: number[]
	relevantOffset: number
	useNewPollDialogInNavigation: boolean
	useNewPollInPollist: boolean
	useCommentsAlternativeStyling: boolean
	useAlternativeStyling: boolean
	verbosePollsList: boolean
	variantsCreation: boolean

}

export type Calendar = {
	key: string
	name: string
	calendarUri: string
	displayColor: string
	permissions: number
}

export type PreferencesStore = {
	user: UserPreferences
	availableCalendars: Calendar[]
}
