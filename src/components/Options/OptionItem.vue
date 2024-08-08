<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, PropType } from 'vue'
	import moment from '@nextcloud/moment'
	import linkifyStr from 'linkify-string'
	import DragIcon from 'vue-material-design-icons/DragHorizontalVariant.vue'
	import { Option, PollType, BoxType } from '../../Types/index.ts'

	const props = defineProps({
		draggable: {
			type: Boolean,
			default: false,
		},
		option: {
			type: Object as PropType<Option>,
			required: true,
		},
		tag: {
			type: String,
			default: 'div',
		},
		display: {
			type: String as PropType<BoxType>,
			default: BoxType.Text,
		},
		pollType: {
			type: String as PropType<PollType>,
			default: PollType.Text,
		},
	})

	const eventOption = computed(() => {
		const from = moment.unix(props.option.timestamp)
		const to = moment.unix(props.option.timestamp + Math.max(0, props.option.duration))
		// does the event start at 00:00 local time and
		// is the duration divisable through 24 hours without rest
		// then we have a day long event (one or multiple days)
		// In this case we want to suppress the display of any time information
		const dayLongEvent = from.unix() === moment(from).startOf('day').unix() && to.unix() === moment(to).startOf('day').unix() && from.unix() !== to.unix()

		const dayModifier = dayLongEvent ? 1 : 0
		// modified to date, in case of day long events, a second gets substracted
		// to set the begin of the to day to the end of the previous date
		const toModified = moment(to).subtract(dayModifier, 'days')

		if (props.pollType !== PollType.Date) {
			return {}
		}
		return {
			from: {
				month: from.format(moment().year() === from.year() ? 'MMM' : 'MMM [ \']YY'),
				day: from.format('D'),
				dow: from.format('ddd'),
				time: from.format('LT'),
				date: from.format('ll'),
				dateTime: from.format('llll'),
				utc: moment(from).utc().format('llll'),
			},
			to: {
				month: toModified.format(moment().year() === toModified.year() ? 'MMM' : 'MMM [ \']YY'),
				day: toModified.format('D'),
				dow: toModified.format('ddd'),
				time: to.format('LT'),
				date: toModified.format('ll'),
				dateTime: to.format('llll'),
				utc: moment(to).utc().format('llll'),
				sameDay: from.format('L') === toModified.format('L'),
			},
			dayLong: dayLongEvent,
		}
	})

	const dateLocalFormat = computed(() => {
		if (props.pollType !== PollType.Date) {
			return {}
		}

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

	const dateLocalFormatUTC = computed(() => 
		props.option.duration
			? `${eventOption.value.from.utc} - ${eventOption.value.to.utc} UTC`
			: `${eventOption.value.from.utc} UTC`
	)

	const optionTooltip = computed(() => {
		if (props.pollType === PollType.Date) {
			return dateLocalFormatUTC.value
		}

		return props.option.text
	})

	const optionText = computed(() => {
		if (props.pollType === PollType.Date) {
			return dateLocalFormat.value
		}

		return linkifyStr(props.option.text)
	})

	const show = computed(() => {
		if (props.pollType === PollType.Date && props.display === BoxType.Date) {
			return BoxType.Date
		}

		return BoxType.Text
	})

</script>

<template>
	<Component :is="tag" class="option-item" :class="{ draggable: props.draggable, deleted: (props.option.deleted !== 0), 'date-box': show === BoxType.Date }">
		<DragIcon v-if="props.draggable" :class="{ draggable: props.draggable }" />

		<slot name="icon" />

		<!-- eslint-disable vue/no-v-html -->
		<div v-if="show === BoxType.Text"
			:title="optionTooltip"
			class="option-item__option--text"
			v-html="optionText" />
		<!-- eslint-enable vue/no-v-html -->

		<div v-if="show === BoxType.Date" :title="dateLocalFormatUTC" class="option-item__option--datebox">
			<div class="event-date">
				<div class="event-from">
					<div class="month">
						{{ eventOption.from.month }}
					</div>
					<div class="day">
						{{ eventOption.from.dow }} {{ eventOption.from.day }}
					</div>
					<div v-if="!eventOption.dayLong" class="time">
						{{ eventOption.from.time }}
						<span v-if="!eventOption.dayLong && option.duration && eventOption.to.sameDay">
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

		<slot name="actions" />
	</Component>
</template>

<style lang="scss">
	.option-item {
		display: flex;
		align-items: center;
		flex: 1;
		position: relative;

		&.deleted {
			opacity: 0.6;
		}

		&.date-box {
			align-items: stretch;
			flex-direction: column;
		}
	}

	.devider {
		align-self: center;
		color: var(--color-text-maxcontrast);
	}

	.option-item__option--datebox {
		display: flex;
		flex-direction: column;
		align-items: stretch;
		justify-content: flex-start;
		text-align: center;
		hyphens: auto;
		white-space: nowrap !important;
	}

	.event-date {
		display: flex;
		flex: 0 1;
		flex-direction: row;
		justify-content: center;

		.event-from, .event-to {
			display: flex;
			flex-direction: column;
			flex: 1;
			min-width: 70px;

			.month, .dow, .time {
				white-space: pre;
				font-size: 1.1em;
				padding: 0 4px;
				color: var(--color-text-maxcontrast);
			}
			.day {
				font-size: 1.2em;
				font-weight: 600;
				margin: 5px 0 5px 0;
				padding: 0 4px;
			}

			.time {
				font-size: 0.8em;
				padding: 0 4px;
			}
		}
	}

	.event-time {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-top: 8px;
		height: 1.5em;
		.time-to {
			font-size: 0.8em;
		}
	}

	[class*='option-item__option'] {
		flex: 1;
		opacity: 1;
		white-space: normal;
	}

	.deleted {
		[class*='option-item__option']::after {
			content: var(--content-deleted);
			font-weight: bold;
			color: var(--color-error-text);
		}
	}

	.option-item__option--text {
		overflow: hidden;
		text-overflow: ellipsis;

		a {
			font-weight: bold;
			text-decoration: underline;
		}
	}

	.option-item__handle {
		margin-right: 8px;
	}

	.draggable {
		cursor: grab;
		&:active {
			cursor: grabbing;
			cursor: -moz-grabbing;
			cursor: -webkit-grabbing;
		}

		.material-design-icon.draggable {
			width: 0;
			padding-right: 0px;
			transition: all .3s ease-in-out;
		}

		&:active,
		&:hover {
			.material-design-icon.draggable {
				width: initial;
				padding-right: 7px;
			}
		}

	}

	.option-item__rank {
		flex: 0 0;
		justify-content: flex-end;
		padding-right: 8px;
	}
</style>
