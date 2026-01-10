<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime, Duration } from 'luxon'
import { getDates } from '../../../composables/optionDateTime'

interface Props {
	startDate: DateTime
	duration?: Duration
}

const { startDate, duration = Duration.fromMillis(0) } = defineProps<Props>()

const optionDateTimes = computed(() => getDates(startDate, duration))
</script>

<template>
	<div :title="optionDateTimes.interval.toISO()" class="datebox">
		<div class="month from" :class="{ span: optionDateTimes.isSameMonth }">
			{{
				optionDateTimes.optionStart.toLocaleString(
					DateTime.now().year === optionDateTimes.optionStart.year
						? { month: 'short' }
						: { month: 'short', year: '2-digit' },
				)
			}}
		</div>

		<div v-if="!optionDateTimes.isSameMonth" class="month to">
			{{
				optionDateTimes.optionEnd.toLocaleString(
					DateTime.now().year === optionDateTimes.optionStart.year
						? { month: 'short' }
						: { month: 'short', year: '2-digit' },
				)
			}}
		</div>

		<div class="day from" :class="{ span: optionDateTimes.isSameDay }">
			{{
				optionDateTimes.optionStart.toLocaleString({
					weekday: 'short',
					day: 'numeric',
				})
			}}
		</div>

		<span v-if="!optionDateTimes.isSameDay" class="day divider">–</span>

		<div v-if="!optionDateTimes.isSameDay" class="day to">
			{{
				optionDateTimes.optionEnd.toLocaleString({
					weekday: 'short',
					day: 'numeric',
				})
			}}
		</div>

		<div
			v-if="!optionDateTimes.isFullDays"
			class="time from"
			:class="{ span: optionDateTimes.isSameDay }">
			{{
				optionDateTimes.isSameDay && !optionDateTimes.isSameTime
					? optionDateTimes.interval.toLocaleString(DateTime.TIME_SIMPLE)
					: optionDateTimes.optionStart.toLocaleString(
							DateTime.TIME_SIMPLE,
						)
			}}
		</div>

		<span
			v-if="!optionDateTimes.isFullDays && !optionDateTimes.isSameDay"
			class="time divider">
			{{ optionDateTimes.isSameDay ? '-' : '&nbsp;' }}
		</span>

		<div
			v-if="!optionDateTimes.isFullDays && !optionDateTimes.isSameDay"
			class="time to">
			{{ optionDateTimes.optionEnd.toLocaleString(DateTime.TIME_SIMPLE) }}
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
