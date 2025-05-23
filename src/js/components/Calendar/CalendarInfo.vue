<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="calendar-info"
		:class="[conflictLevel, statusClass]"
		:style="calendarStyle">
		<div class="calendar-info__time">
			{{ showJustDays ? dayDisplay : timeDisplay }}
		</div>
		<div class="summay" :class="statusClass">
			{{ calendarEvent.summary }}
		</div>
	</div>
</template>

<script>

import moment from '@nextcloud/moment'
export default {
	name: 'CalendarInfo',

	props: {
		calendarEvent: {
			type: Object,
			default: undefined,
		},

		option: {
			type: Object,
			default: undefined,
		},

	},

	computed: {
		calendarStyle() {
			return {
				backgroundColor: this.calendarEvent.displayColor,
				color: this.fontColor,
			}
		},

		dayStart() {
			return moment.unix(this.calendarEvent.start).format('ddd')
		},

		dayEnd() {
			return moment.unix(this.calendarEvent.end - 1).format('ddd')
		},

		dayDisplay() {
			if (this.dayEnd === this.dayStart) {
				return this.dayStart
			}

			return `${this.dayStart} - ${this.dayEnd}`
		},

		timeStart() {
			return moment.unix(this.calendarEvent.start).format('LT')
		},

		timeEnd() {
			return moment.unix(this.calendarEvent.end).format('LT')
		},

		timeDisplay() {
			if (this.timeEnd === this.timeStart) {
				return this.timeStart
			}
			return `${this.timeStart} - ${this.timeEnd}`
		},

		showJustDays() {
			return this.dayStart !== this.dayEnd || this.calendarEvent.allDay
		},

		statusClass() {
			return this.calendarEvent.status.toLowerCase()
		},

		fontColor() {
			if (this.calendarEvent.displayColor === 'transparent') {
				return 'var(--color-main-text)'
			}

			const hex = this.calendarEvent.displayColor.replace(/#/, '')
			const r = parseInt(hex.slice(0, 2), 16)
			const g = parseInt(hex.slice(2, 4), 16)
			const b = parseInt(hex.slice(4, 6), 16)

			const l = [
				0.299 * r,
				0.587 * g,
				0.114 * b,
			].reduce((a, b) => a + b) / 255

			return l > 0.5 ? '#222' : '#ddd'
		},

		conflictLevel() {
			if (this.calendarEvent.calendarKey === 0) {
				return 'conflict-ignore'
			}

			// No conflict, if calendarEvent starts after end of option
			if (this.calendarEvent.start >= this.option.timestamp + this.option.duration) {
				return 'conflict-no'
			}

			// No conflict, if calendarEvent ends before option
			if (this.calendarEvent.end <= this.option.timestamp) {
				return 'conflict-no'
			}

			return 'conflict-yes'
		},
	},
}

</script>

<style lang="scss">

.calendar-info {
	display: flex;
	align-items: center;
	border-radius: var(--border-radius);
	margin: 4px 0;
	padding: 0 4px;

	.summay {
		margin-inline-start: 4px;
	}

	&.conflict-ignore {
		border-inline-start: 4px solid transparent;
		.summay {
			font-weight: bold;
		}
	}

	&.conflict-no {
		border-inline-start: 4px solid var(--color-success);
	}

	&.cancelled {
		text-decoration: line-through;
		opacity: 0.5;
	}

	&.tentative {
		opacity: 0.5;
	}

	&.conflict-yes {
		border-inline-start: 4px solid var(--color-error);
	}
}

.calendar-info__time {
	width: 65px;
	font-size: 0.8em;
	flex: 0 auto;
}
</style>
