/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type DateTimeUnits = 'minute' | 'hour' | 'day' | 'week' | 'month' | 'year'

export type DateTimeUnitType = {
	id: DateTimeUnits
	name: string
	timeOption: boolean
}
export type TimeZoneTypes = 'local' | 'poll'

export type TimeZoneOption = {
	label: string
	value: TimeZoneTypes
}

export type TimeUnitsType = {
	unit: DateTimeUnitType
	value: number
}

export type DurationType = {
	unit: DateTimeUnitType
	amount: number
}

export type DateFormats = 'dateTime' | 'dateShort'
