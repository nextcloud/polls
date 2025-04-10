<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, PropType } from 'vue'
import moment from '@nextcloud/moment'
import { Option, BoxType } from '../../Types/index.ts'
import { DateTimeDetails } from '../../constants/dateUnits.ts'

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		required: true,
	},
	display: {
		type: String as PropType<BoxType>,
		default: BoxType.Date,
	},
})

const eventOption = computed(
	(): { from: DateTimeDetails; to: DateTimeDetails; dayLong: boolean } => {
		const from = moment.unix(props.option.timestamp)
		const to = moment.unix(
			props.option.timestamp + Math.max(0, props.option.duration),
		)
		// does the event start at 00:00 local time and
		// is the duration divisable through 24 hours without rest
		// then we have a day long event (one or multiple days)
		// In this case we want to suppress the display of any time information
		const dayLongEvent =
			from.unix() === moment(from).startOf('day').unix()
			&& to.unix() === moment(to).startOf('day').unix()
			&& from.unix() !== to.unix()

		const dayModifier = dayLongEvent ? 1 : 0
		// modified to date, in case of day long events, a second gets substracted
		// to set the begin of the to day to the end of the previous date
		const toModified = moment(to).subtract(dayModifier, 'days')

		return {
			from: {
				month: from.format(
					moment().year() === from.year() ? 'MMM' : "MMM [ ']YY",
				),
				day: from.format('D'),
				dow: from.format('ddd'),
				time: from.format('LT'),
				date: from.format('ll'),
				dateTime: from.format('llll'),
				iso: moment(from).toISOString(),
				utc: moment(from).utc().format('llll'),
			},
			to: {
				month: toModified.format(
					moment().year() === toModified.year() ? 'MMM' : "MMM [ ']YY",
				),
				day: toModified.format('D'),
				dow: toModified.format('ddd'),
				time: to.format('LT'),
				date: toModified.format('ll'),
				dateTime: to.format('llll'),
				utc: moment(to).utc().format('llll'),
				iso: moment(to).toISOString(),
				sameDay: from.format('L') === toModified.format('L'),
			},
			dayLong: dayLongEvent,
		}
	},
)

const dateLocalFormatUTC = computed(() =>
	props.option.duration
		? `${eventOption.value.from.utc} - ${eventOption.value.to.utc} UTC`
		: `${eventOption.value.from.utc} UTC`,
)

const dateLocalFormat = computed(() => {
	if (props.option.duration === 0) {
		return eventOption.value.from.dateTime
	}

	if (eventOption.value.dayLong && eventOption.value.to.sameDay) {
		return eventOption.value.from.date
	}

	if (eventOption.value.dayLong && !eventOption.value.to.sameDay) {
		return `${eventOption.value.from.date} - ${eventOption.value.to.date}`
	}

	if (eventOption.value.to.sameDay) {
		return `${eventOption.value.from.dateTime} - ${eventOption.value.to.time}`
	}

	return `${eventOption.value.from.dateTime} - ${eventOption.value.to.dateTime}`
})
</script>

<template>
	<div
		v-if="props.display === BoxType.Text"
		:title="dateLocalFormatUTC"
		class="option-item__option--text">
		{{ dateLocalFormat }}
	</div>

	<div v-else :title="dateLocalFormatUTC" class="option-item__option--datebox">
		<div
			:class="[
				'event-date',
				{ aligned: props.display === BoxType.AlignedText },
			]">
			<div class="event-from">
				<div class="month">
					{{ eventOption.from.month }}
				</div>
				<div class="day">
					{{ eventOption.from.dow }} {{ eventOption.from.day }}
				</div>
				<div v-if="!eventOption.dayLong" class="time">
					{{ eventOption.from.time }}
					<span
						v-if="
							!eventOption.dayLong
							&& option.duration
							&& eventOption.to.sameDay
						">
						- {{ eventOption.to.time }}
					</span>
				</div>
			</div>

			<div v-if="option.duration && !eventOption.to.sameDay" class="devider">
				-
			</div>

			<div v-if="option.duration && !eventOption.to.sameDay" class="event-to">
				<div class="month">
					{{ eventOption.to.month }}
				</div>
				<div class="day">
					{{ eventOption.to.dow }} {{ eventOption.to.day }}
				</div>
				<div v-if="!eventOption.dayLong" class="time">
					{{ eventOption.to.time }}
				</div>
			</div>
		</div>
	</div>
</template>

<style lang="scss">
.option-item__option--datebox {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	justify-content: flex-start;
	text-align: center;
	hyphens: auto;
	white-space: nowrap !important;

	.devider {
		align-self: center;
		color: var(--color-text-maxcontrast);
		padding: 0 0.25em;
	}

	.event-date {
		display: flex;
		flex: 0 1;
		justify-content: center;

		.event-from,
		.event-to {
			display: flex;
			flex-direction: column;
			min-width: 70px;

			.month,
			.dow,
			.time {
				white-space: pre;
				font-size: 1.1em;
				padding: 0 0.25em;
				color: var(--color-text-maxcontrast);
			}
			.day {
				font-size: 1.2em;
				font-weight: 600;
				margin: 0.33em 0 0.33em 0;
				padding: 0 0.25em;
			}

			.time {
				font-size: 0.8em;
				padding: 0 0.25em;
			}
		}
	}

	.event-time {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-top: 0.5em;
		height: 1.5em;
		.time-to {
			font-size: 0.8em;
		}
	}
}
</style>
