<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime, Duration, Interval } from 'luxon'

interface Props {
	dateTime: DateTime
	duration?: Duration
}
const { dateTime, duration = Duration.fromMillis(0) } = defineProps<Props>()

// the dates span one or more entire days
// do not display the time in this case
const allDay = computed(
	() =>
		dateTime.startOf('day').toSeconds() === dateTime.toSeconds()
		&& duration.as('seconds') % 86400 === 0,
)

// 'to' is 'from' plus the duration
// subtract a day if allDay is true and luxonDuration is greater than 0 to match the
// end of the day after the duration instead of the beginning of the next day
const to = computed(() => {
	// this is a quick fix which intercepts dayspans crossing daylight saving time changes
	// Adding duration as seconds divided by a normal dayspan in seconds (86400) multiplied
	// by 86400 ensures that we always land at the same time of day, even if the actual
	// dayspan is 23 or 25 hours due to DST changes
	// FIXME: this should be replaced by a more stable solution
	if (allDay.value) {
		return dateTime.plus((duration.as('seconds') / 86400) * 86400)
	}
	return dateTime
		.plus(duration)
		.minus({ day: allDay.value && duration.as('seconds') > 0 ? 1 : 0 })
})

// to and from dates have the same month (and year)
// suppress the 'to' month if they are the same
const isSameMonth = computed(
	() => dateTime.month === to.value.month && dateTime.year === to.value.year,
)

// to and from dates have the same day (in the same month and year)
// suppress the 'to' day if they are the same
// display the interval as timespan inside the same day
const isSameDay = computed(() => dateTime.day === to.value.day && isSameMonth.value)

// Shortcut: 'to' and 'from' are identical
// suppress the 'to' time if they are the same
const isSameTime = computed(() => duration.as('seconds') === 0)

const interval = computed(() => Interval.fromDateTimes(dateTime, to.value))
</script>

<template>
	<div :title="interval.toISO()" class="datebox">
		<div class="month from" :class="{ span: isSameMonth }">
			{{
				dateTime.toLocaleString(
					DateTime.now().year === dateTime.year
						? { month: 'short' }
						: { month: 'short', year: '2-digit' },
				)
			}}
		</div>

		<div v-if="!isSameMonth" class="month to">
			{{
				to.toLocaleString(
					DateTime.now().year === dateTime.year
						? { month: 'short' }
						: { month: 'short', year: '2-digit' },
				)
			}}
		</div>

		<div class="day from" :class="{ span: isSameDay }">
			{{ dateTime.toLocaleString({ weekday: 'short', day: 'numeric' }) }}
		</div>

		<span v-if="!isSameDay" class="day divider">–</span>

		<div v-if="!isSameDay" class="day to">
			{{ to.toLocaleString({ weekday: 'short', day: 'numeric' }) }}
		</div>

		<div v-if="!allDay" class="time from" :class="{ span: isSameDay }">
			{{
				isSameDay && !isSameTime
					? interval.toLocaleString(DateTime.TIME_SIMPLE)
					: dateTime.toLocaleString(DateTime.TIME_SIMPLE)
			}}
		</div>

		<span v-if="!allDay && !isSameDay" class="time divider">
			{{ isSameDay ? '-' : '&nbsp;' }}
		</span>

		<div v-if="!allDay && !isSameDay" class="time to">
			{{ to.toLocaleString(DateTime.TIME_SIMPLE) }}
		</div>
	</div>
</template>

<style lang="scss" scoped>
.datebox {
	display: grid;
	grid-template: 1.6rem 2rem 1rem / auto 1.5rem auto;
	row-gap: 0.33rem;
	max-width: 11rem;
	margin: auto;
	text-align: center;
	hyphens: auto;
	white-space: nowrap;

	.list-view & {
		grid-template: 1.6rem 2rem auto/auto 1.5rem auto;
	}

	.from {
		grid-column: 1;
		&.span {
			grid-column: 1 / span 3;
		}
	}
	.divider {
		grid-column: 2;
	}
	.to {
		grid-column: 3;
	}

	.month {
		font-size: 1.1em;
		color: var(--color-text-maxcontrast);
	}

	.day {
		font-size: 1.2em;
		font-weight: 600;
	}

	.time {
		font-size: 0.8em;
		color: var(--color-text-maxcontrast);
		.to.same-day {
			text-align: start;
		}
		.from.same-day {
			text-align: end;
		}
	}
}
</style>
