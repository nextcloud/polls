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

export type DateUnit = {
	name: string
	value: DateUnitValue
}

export const dateUnits: DateUnit[] = [
	{ name: t('polls', 'Minute'), value: DateUnitValue.Minute },
	{ name: t('polls', 'Hour'), value: DateUnitValue.Hour },
	{ name: t('polls', 'Day'), value: DateUnitValue.Day },
	{ name: t('polls', 'Week'), value: DateUnitValue.Week },
	{ name: t('polls', 'Month'), value: DateUnitValue.Month },
	{ name: t('polls', 'Year'), value: DateUnitValue.Year },
]
