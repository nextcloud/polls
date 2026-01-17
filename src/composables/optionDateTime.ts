/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { useSessionStore } from '@/stores/session'
import { DateTime, Duration, Interval } from 'luxon'
import { computed, ref } from 'vue'

/**
 * returns the width of the element with the given id
 *
 * @param elementId the id of the element whose width should be checked
 * @param elWidthOffset the width offset to check against
 */

export const dateFrom = ref()

export function getDates(optionStart: DateTime, optionDuration: Duration | null) {
	const sessionStore = useSessionStore()

	// startDate equals optionStart in user timezone
	const computedStartDate = computed(() =>
		optionStart.setZone(sessionStore.currentIANAZone),
	)

	// duration equals optionDuration with special handling for full days
	const computedDuration = computed(() => {
		if (
			optionDuration?.as('days') === 0
			&& computedStartDate.value.valueOf()
				=== computedStartDate.value.startOf('day').valueOf()
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
			computedStartDate.value.valueOf()
				=== computedStartDate.value.startOf('day').valueOf()
			&& computedDuration.value.hours + computedDuration.value.minutes === 0
		)
	})

	const computedEndDate = computed(() =>
		computedStartDate.value
			.plus(computedDuration.value || Duration.fromObject({}))
			// If full days are selected, subtract 1 millisecond for display purposes
			.minus({ milliseconds: computedFullDays.value ? 1 : 0 }),
	)

	const computedInterval = computed(() =>
		Interval.fromDateTimes(computedStartDate.value, computedEndDate.value),
	)

	const computedIsSameMonth = computed(() =>
		computedStartDate.value.hasSame(computedEndDate.value, 'month'),
	)

	const computedIsSameDay = computed(() =>
		computedStartDate.value.hasSame(computedEndDate.value, 'day'),
	)

	const computedIsSameTime = computed(
		() => computedDuration.value?.as('minutes') === 0,
	)

	return {
		optionStart: computedStartDate.value,
		duration: computedDuration.value,
		optionEnd: computedEndDate.value,
		isFullDays: computedFullDays.value,
		isSameMonth: computedIsSameMonth.value,
		isSameDay: computedIsSameDay.value,
		isSameTime: computedIsSameTime.value,
		interval: computedInterval.value,
	}
}
