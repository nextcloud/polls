/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Option } from '@/stores/options.types'
import { DateTime, Duration, Interval } from 'luxon'

type OptionDateTime = {
	optionStart: DateTime
	duration: Duration
	optionEnd: DateTime
	isFullDays: boolean
	isSameMonth: boolean
	isSameDay: boolean
	isSameTime: boolean
	interval: Interval
}

export function getDatesFromOption(
	option: Option,
	timezone: string = Intl.DateTimeFormat().resolvedOptions().timeZone,
): OptionDateTime {
	return getDates(option.getDateTime(), option.getDuration(), timezone)
}
/**
 *
 * @param optionStart DateTime object representing the start date and time
 * @param optionDuration  Duration object representing the duration
 * @param timezone string representing the timezone (defaults to the user's local timezone)
 * @return OptionDateTime object containing computed date and time information of the option
 */
export function getDates(
	optionStart: DateTime,
	optionDuration: Duration,
	timezone: string = Intl.DateTimeFormat().resolvedOptions().timeZone,
): OptionDateTime {
	optionStart = optionStart.setZone(timezone)

	// Special handling for full-day options with zero duration: treat as 1 day
	const duration =
		optionDuration.as('days') === 0
		&& optionStart.valueOf() === optionStart.startOf('day').valueOf()
			? Duration.fromObject({ days: 1 })
			: optionDuration

	const isFullDays =
		optionStart.valueOf() === optionStart.startOf('day').valueOf()
		&& duration.hours + duration.minutes === 0

	// Subtract 1 millisecond from full-day end dates for display purposes
	const optionEnd = optionStart
		.plus(duration)
		.minus({ milliseconds: isFullDays ? 1 : 0 })

	return {
		optionStart,
		duration,
		optionEnd,
		isFullDays,
		isSameMonth: optionStart.hasSame(optionEnd, 'month'),
		isSameDay: optionStart.hasSame(optionEnd, 'day'),
		isSameTime: duration.as('minutes') === 0,
		interval: Interval.fromDateTimes(optionStart, optionEnd),
	}
}
