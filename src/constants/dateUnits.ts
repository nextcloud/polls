/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { createRecordFromArray } from '../helpers/modules/arrayHelper'

export enum DateUnitKeys {
	Minute = 'minute',
	Hour = 'hour',
	Day = 'days',
	Week = 'week',
	Month = 'month',
	Year = 'year',
}

export enum TimeUnitKeys {
	Minute = 'minute',
	Hour = 'hour',
}

export enum DateTimeUnitKeys {
	Minute = 'minute',
	Hour = 'hour',
	Day = 'day',
	Week = 'week',
	Month = 'month',
	Year = 'year',
}

export enum DateTypeKeys {
	Date = 'date',
	DateTime = 'dateTime',
	DateRange = 'dateRange',
	DateTimeRange = 'dateTimeRange',
}

export type DateTimeUnitType = {
	id: DateUnitKeys
	name: string
	luxonUnit: 'year' | 'month' | 'week' | 'day' | 'hour' | 'minute'
}

export type TimeUnitsType = {
	unit: DateTimeUnitType
	value: number
}

export type DateOptionTypeSelect = {
	id: DateTypeKeys
	name: string
}

export type DurationType = {
	unit: DateTimeUnitType
	amount: number
}

export type DateTimeDetails = {
	month: string
	day: string
	dow: string
	time: string
	date: string
	dateTime: string
	iso: string
	utc: string
	sameDay?: boolean
}

export const dateTimeUnits: DateTimeUnitType[] = [
	{
		id: DateUnitKeys.Minute,
		name: t('polls', 'Minute'),
		luxonUnit: 'minute',
	},
	{
		id: DateUnitKeys.Hour,
		name: t('polls', 'Hour'),
		luxonUnit: 'hour',
	},
	{
		id: DateUnitKeys.Day,
		name: t('polls', 'Day'),
		luxonUnit: 'day',
	},
	{
		id: DateUnitKeys.Week,
		name: t('polls', 'Week'),
		luxonUnit: 'week',
	},
	{
		id: DateUnitKeys.Month,
		name: t('polls', 'Month'),
		luxonUnit: 'month',
	},
	{
		id: DateUnitKeys.Year,
		name: t('polls', 'Year'),
		luxonUnit: 'year',
	},
]

// Create a record from the dateUnits array
export const dateTimeUnitsKeyed: Record<DateUnitKeys, DateTimeUnitType> =
	createRecordFromArray(dateTimeUnits)

export const dateOnlyUnits: DateTimeUnitType[] = [
	{
		name: t('polls', 'Day'),
		id: DateUnitKeys.Day,
		luxonUnit: 'day',
	},
	{
		name: t('polls', 'Week'),
		id: DateUnitKeys.Week,
		luxonUnit: 'week',
	},
	{
		name: t('polls', 'Month'),
		id: DateUnitKeys.Month,
		luxonUnit: 'month',
	},
	{
		name: t('polls', 'Year'),
		id: DateUnitKeys.Year,
		luxonUnit: 'year',
	},
]
