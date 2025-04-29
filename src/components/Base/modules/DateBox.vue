<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, PropType } from 'vue'
import { DateTime, Duration, Interval } from 'luxon'
import { Option } from '../../../Types/index.ts'
import { useSessionStore } from '../../../stores/session.ts'

const sessionStore = useSessionStore()
const props = defineProps({
	luxonDate: {
		type: Object as PropType<DateTime>,
		default: null,
	},
	luxonDuration: {
		type: Object as PropType<Duration>,
		default: null,
	},
	option: {
		type: Object as PropType<Option>,
		default: null,
	},
})

const from = computed(() => {
	if (props.option) {
		return DateTime.fromSeconds(props.option.timestamp).setLocale(
			sessionStore.currentUser.languageCode,
		)
	}
	return props.luxonDate.setLocale(sessionStore.currentUser.languageCode)
})

const duration = computed(() => {
	if (props.option) {
		return Duration.fromMillis(props.option.duration * 1000)
	}
	return props.luxonDuration
})

// the dates span one or more entire days
// do not display the time in this case
const allDay = computed(
	() =>
		from.value.startOf('day').toSeconds() === from.value.toSeconds()
		&& duration.value.as('seconds') % 86400 === 0,
)

// 'to' is 'from' plus the duration
// subtract a day if allDay is true and duration is greater than 0 to match the
// end of the day after the duration instead of the beginning of the next day
const to = computed(() =>
	from.value
		.plus(duration.value)
		.minus({ day: allDay.value && duration.value.as('seconds') > 0 ? 1 : 0 }),
)

// to and from dates have the same month (and year)
// suppress the 'to' month if they are the same
const isSameMonth = computed(
	() => from.value.month === to.value.month && from.value.year === to.value.year,
)

// to and from dates have the same day (in the same month and year)
// suppress the 'to' day if they are the same
// display the interval as timespan inside the same day
const isSameDay = computed(
	() => from.value.day === to.value.day && isSameMonth.value,
)

// Shortcut: 'to' and 'from' are identical
// suppress the 'to' time if they are the same
const isSameTime = computed(() => duration.value.as('seconds') === 0)

const interval = computed(() =>
	Interval.fromDateTimes(from.value.toUTC(), to.value.toUTC()),
)
</script>

<template>
	<div :title="interval.toISO()" class="datebox">
		<div class="month from" :class="{ span: isSameMonth }">
			{{
				from.toLocaleString(
					DateTime.now().year === from.year
						? { month: 'short' }
						: { month: 'short', year: '2-digit' },
				)
			}}
		</div>

		<div v-if="!isSameMonth" class="month to">
			{{
				to.toLocaleString(
					DateTime.now().year === from.year
						? { month: 'short' }
						: { month: 'short', year: '2-digit' },
				)
			}}
		</div>

		<div class="day from" :class="{ span: isSameDay }">
			{{ from.toLocaleString({ weekday: 'short', day: 'numeric' }) }}
		</div>

		<span v-if="!isSameDay" class="day divider">–</span>

		<div v-if="!isSameDay" class="day to">
			{{ to.toLocaleString({ weekday: 'short', day: 'numeric' }) }}
		</div>

		<div v-if="!allDay" class="time from" :class="{ span: isSameDay }">
			{{
				isSameDay && !isSameTime
					? interval.toLocaleString(DateTime.TIME_SIMPLE)
					: from.toLocaleString(DateTime.TIME_SIMPLE)
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
