/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DateTime, Duration, Interval } from 'luxon'
import { ref, toValue, watchEffect } from 'vue'

/**
 * returns the width of the element with the given id
 *
 * @param elementId the id of the element whose width should be checked
 * @param elWidthOffset the width offset to check against
 */

export const dateFrom = ref()

export function getDates(optionStart: DateTime, optionDuration: Duration | null) {
	const endDate = ref(optionStart)
	const localDuration = ref(null as Duration | null)
	const interval = ref(Interval.fromDateTimes(optionStart, optionStart))

	const fullDays = ref(false)
	const isSameMonth = ref(false)
	const isSameDay = ref(false)
	const isSameTime = ref(false)

	const calculateValues = () => {
		endDate.value = toValue(optionStart)
		localDuration.value = toValue(optionDuration)
		fullDays.value = false

		if (!localDuration.value) {
			// without duration, no further calculation is possible
			// end date remains the same as from date
			return
		}

		// Check if the duration represents full days
		fullDays.value =
			optionStart.valueOf() === optionStart.startOf('day').valueOf()
			&& localDuration.value.hours + localDuration.value.minutes === 0

		// If full days are selected and duration is 0 days, set duration to 1 day
		if (fullDays.value && localDuration.value.as('days') === 0) {
			optionDuration = Duration.fromObject({ days: 1 })
		}

		endDate.value = optionStart.plus(localDuration.value)
		// If full days are selected, subtract 1 millisecond for display purposes
		if (fullDays.value) {
			endDate.value = endDate.value.minus({ milliseconds: 1 })
		}
		isSameMonth.value = optionStart.hasSame(endDate.value, 'month')
		isSameDay.value = optionStart.hasSame(endDate.value, 'day')
		isSameTime.value = localDuration.value.as('minutes') === 0
		interval.value = Interval.fromDateTimes(optionStart, endDate.value)
	}

	watchEffect(() => {
		calculateValues()
	})

	return {
		optionStart,
		optionEnd: endDate.value,
		isFullDays: fullDays.value,
		isSameMonth: isSameMonth.value,
		isSameDay: isSameDay.value,
		isSameTime: isSameTime.value,
		optionInterval: interval.value,
	}
}
