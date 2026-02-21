/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DateTime, Duration, Interval } from 'luxon'
import { computed } from 'vue'

type OptionDateTime = {
	optionStart: DateTime
	duration: Duration | null
	optionEnd: DateTime
	isFullDays: boolean
	isSameMonth: boolean
	isSameDay: boolean
	isSameTime: boolean
	interval: Interval
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
	optionDuration: Duration | null,
	timezone: string = Intl.DateTimeFormat().resolvedOptions().timeZone,
): OptionDateTime {
	optionStart = optionStart.setZone(timezone)
	// duration equals optionDuration with special handling for full days
	const computedDuration = computed(() => {
		if (
			optionDuration?.as('days') === 0
			&& optionStart.valueOf() === optionStart.startOf('day').valueOf()
		) {
			return Duration.fromObject({ days: 1 })
		}

		return optionDuration
	})

	const computedFullDays = computed(() => {
		if (computedDuration.value === null) {
			return false
		}

		return (
			optionStart.valueOf() === optionStart.startOf('day').valueOf()
			&& computedDuration.value.hours + computedDuration.value.minutes === 0
		)
	})

	const computedEndDate = computed(() =>
		optionStart
			.plus(computedDuration.value || Duration.fromObject({}))
			// If full days are selected, subtract 1 millisecond for display purposes
			.minus({ milliseconds: computedFullDays.value ? 1 : 0 }),
	)

	const computedInterval = computed(() =>
		Interval.fromDateTimes(optionStart, computedEndDate.value),
	)

	const computedIsSameMonth = computed(() =>
		optionStart.hasSame(computedEndDate.value, 'month'),
	)

	const computedIsSameDay = computed(() =>
		optionStart.hasSame(computedEndDate.value, 'day'),
	)

	const computedIsSameTime = computed(
		() => computedDuration.value?.as('minutes') === 0,
	)

	return {
		optionStart,
		duration: computedDuration.value,
		optionEnd: computedEndDate.value,
		isFullDays: computedFullDays.value,
		isSameMonth: computedIsSameMonth.value,
		isSameDay: computedIsSameDay.value,
		isSameTime: computedIsSameTime.value,
		interval: computedInterval.value,
	}
}
