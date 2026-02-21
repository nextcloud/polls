/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import type { DateTimeUnits, DateTimeUnitType } from '../../Types/dateTime'
import { DateTime } from 'luxon'

/**
 * Set time to next timeStep
 * @param dateTime DateTime
 * @param timeStep in minutes
 * @return DateTime
 */
export function ceilDate(dateTime: DateTime, timeStep: number): DateTime {
	return dateTime.set({
		minute: (Math.ceil(dateTime.minute / timeStep) * timeStep) % 60,
	})
}

export const dateTimeUnitsKeyed: Record<DateTimeUnits, DateTimeUnitType> = {
	minute: {
		id: 'minute',
		name: t('polls', 'Minute'),
		timeOption: true,
	},
	hour: {
		id: 'hour',
		name: t('polls', 'Hour'),
		timeOption: true,
	},
	day: {
		id: 'day',
		name: t('polls', 'Day'),
		timeOption: false,
	},
	week: {
		id: 'week',
		name: t('polls', 'Week'),
		timeOption: false,
	},
	month: {
		id: 'month',
		name: t('polls', 'Month'),
		timeOption: false,
	},
	year: {
		id: 'year',
		name: t('polls', 'Year'),
		timeOption: false,
	},
}
