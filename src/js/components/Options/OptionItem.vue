<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Component :is="tag" class="option-item" :class="{ draggable: isDraggable, deleted: option.deleted, 'date-box': show === 'dateBox' }">
		<DragIcon v-if="isDraggable" :class="{ draggable: isDraggable }" />

		<slot name="icon" />

		<!-- eslint-disable vue/no-v-html -->
		<div v-if="show === 'textBox'"
			:title="optionTooltip"
			class="option-item__option--text"
			v-html="optionText" />
		<!-- eslint-enable vue/no-v-html -->

		<div v-if="show === 'dateBox'" :title="dateLocalFormatUTC" class="option-item__option--datebox">
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

<script>
import moment from '@nextcloud/moment'
import linkifyStr from 'linkify-string'
import DragIcon from 'vue-material-design-icons/DragHorizontalVariant.vue'

export default {
	name: 'OptionItem',

	components: {
		DragIcon,
	},

	props: {
		draggable: {
			type: Boolean,
			default: false,
		},
		option: {
			type: Object,
			required: true,
		},
		tag: {
			type: String,
			default: 'div',
		},
		display: {
			type: String,
			default: 'textBox',
			validator(value) {
				return ['textBox', 'dateBox'].includes(value)
			},
		},
		pollType: {
			type: String,
			default: 'textPoll',
			validator(value) {
				return ['textPoll', 'datePoll'].includes(value)
			},
		},
	},

	computed: {
		isDraggable() {
			return this.draggable
		},

		eventOption() {
			const from = moment.unix(this.option.timestamp)
			const to = moment.unix(this.option.timestamp + Math.max(0, this.option.duration))
			// does the event start at 00:00 local time and
			// is the duration divisable through 24 hours without rest
			// then we have a day long event (one or multiple days)
			// In this case we want to suppress the display of any time information
			const dayLongEvent = from.unix() === moment(from).startOf('day').unix() && to.unix() === moment(to).startOf('day').unix() && from.unix() !== to.unix()

			const dayModifier = dayLongEvent ? 1 : 0
			// modified to date, in case of day long events, a second gets substracted
			// to set the begin of the to day to the end of the previous date
			const toModified = moment(to).subtract(dayModifier, 'days')

			if (this.pollType !== 'datePoll') {
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

		},

		dateLocalFormat() {
			if (this.pollType !== 'datePoll') {
				return {}
			}

			if (this.option.duration === 0) {
				return this.eventOption.from.dateTime
			}

			if (this.eventOption.dayLong && this.eventOption.to.sameDay) {
				return this.eventOption.from.date
			}

			if (this.eventOption.dayLong && !this.eventOption.to.sameDay) {
				return `${this.eventOption.from.date} - ${this.eventOption.to.date}`
			}

			if (this.eventOption.to.sameDay) {
				return `${this.eventOption.from.dateTime} - ${this.eventOption.to.time}`
			}

			return `${this.eventOption.from.dateTime} - ${this.eventOption.to.dateTime}`
		},

		dateLocalFormatUTC() {
			if (this.option.duration) {
				return `${this.eventOption.from.utc} - ${this.eventOption.to.utc} UTC`
			}

			return `${this.eventOption.from.utc} UTC`
		},

		optionTooltip() {
			if (this.pollType === 'datePoll') {
				return this.dateLocalFormatUTC
			}

			return this.option.text
		},

		optionText() {
			if (this.pollType === 'datePoll') {
				return this.dateLocalFormat
			}

			return linkifyStr(this.option.text)
		},

		show() {
			if (this.pollType === 'datePoll' && this.display === 'dateBox') {
				return 'dateBox'
			}

			return 'textBox'
		},
	},
}
</script>

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
		margin-inline-end: 8px;
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
			padding-inline-end: 0px;
			transition: all .3s ease-in-out;
		}

		&:active,
		&:hover {
			.material-design-icon.draggable {
				width: initial;
				padding-inline-end: 7px;
			}
		}

	}

	.option-item__rank {
		flex: 0 0;
		justify-content: flex-end;
		padding-inline-end: 8px;
	}
</style>
