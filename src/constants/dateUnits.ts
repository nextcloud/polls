/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

export enum DateUnitKeys {
	Minute = 'minute',
	Hour = 'hour',
	Day = 'days',
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

export type DateUnitType = {
	name: string
	key: DateUnitKeys
}

export type TimeUnitsType = {
	unit: DateUnitType
	value: number
}

export type DateOptionTypeSelect = {
	name: string
	key: DateTypeKeys
}

export type DurationType = {
	unit: DateUnitType
	amount: number
}

export const dateUnits: DateUnitType[] = [
	{
		name: t('polls', 'Minute'),
		key: DateUnitKeys.Minute,
	},
	{
		name: t('polls', 'Hour'),
		key: DateUnitKeys.Hour,
	},
	{
		name: t('polls', 'Day'),
		key: DateUnitKeys.Day,
	},
	{
		name: t('polls', 'Week'),
		key: DateUnitKeys.Week,
	},
	{
		name: t('polls', 'Month'),
		key: DateUnitKeys.Month,
	},
	{
		name: t('polls', 'Year'),
		key: DateUnitKeys.Year,
	},
]

export const dateOnlyUnits: DateUnitType[] = [
	{
		name: t('polls', 'Day'),
		key: DateUnitKeys.Day,
	},
	{
		name: t('polls', 'Week'),
		key: DateUnitKeys.Week,
	},
	{
		name: t('polls', 'Month'),
		key: DateUnitKeys.Month,
	},
	{
		name: t('polls', 'Year'),
		key: DateUnitKeys.Year,
	},
]

