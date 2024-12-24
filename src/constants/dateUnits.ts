/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

export enum DateUnitValue {
	Minute = 'minute',
	Hour = 'hour',
	Day = 'day',
	Week = 'week',
	Month = 'month',
	Year = 'year',
}

export enum DateTypeValue {
	Date = 'date',
	DateTime = 'dateTime',
	DateRange = 'dateRange',
	DateTimeRange = 'dateTimeRange',
}


export type DateUnitSelect = {
	name: string
	value: DateUnitValue
}

export type TimeUnits = {
	value: number
	unit: DateUnitSelect
}

export type DateOptionTypeSelect = {
	name: string
	value: DateTypeValue
}

export const dateUnits: DateUnitSelect[] = [
	{ name: t('polls', 'Minute'), value: DateUnitValue.Minute },
	{ name: t('polls', 'Hour'), value: DateUnitValue.Hour },
	{ name: t('polls', 'Day'), value: DateUnitValue.Day },
	{ name: t('polls', 'Week'), value: DateUnitValue.Week },
	{ name: t('polls', 'Month'), value: DateUnitValue.Month },
	{ name: t('polls', 'Year'), value: DateUnitValue.Year },
]
