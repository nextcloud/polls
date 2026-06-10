/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type CalendarEvent = {
	id: number
	UID: number
	calendarUri: string
	calendarKey: number
	calendarName: string
	displayColor: string
	allDay: boolean
	description: string
	start: number
	location: string
	end: number
	status: string
	summary: string
	type: 'date' | 'dateTime'
	busy: boolean
}
