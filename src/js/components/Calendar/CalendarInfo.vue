<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="calendar-info"
		:class="[conflictLevel, statusClass]"
		:style="calendarStyle">
		<div v-if="!event.allDay" class="calendar-info__time">
			{{ formatDate(event.start) }} - {{ formatDate(event.end) }}
		</div>
		<div class="calendar-info__summay" :class="statusClass">
			{{ event.summary }}
		</div>
	</div>
</template>

<script>

import moment from '@nextcloud/moment'
export default {
	name: 'CalendarInfo',

	props: {
		event: {
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
				backgroundColor: this.event.displayColor,
				color: this.fontColor,
			}
		},

		statusClass() {
			return this.event.status.toLowerCase()
		},

		fontColor() {
			if (this.event.displayColor === 'transparent') {
				return 'var(--color-main-text)'
			}

			const hex = this.event.displayColor.replace(/#/, '')
			const r = parseInt(hex.substr(0, 2), 16)
			const g = parseInt(hex.substr(2, 2), 16)
			const b = parseInt(hex.substr(4, 2), 16)

			const l = [
				0.299 * r,
				0.587 * g,
				0.114 * b,
			].reduce((a, b) => a + b) / 255

			return l > 0.5 ? '#222' : '#ddd'
		},

		conflictLevel() {
			if (this.event.calendarKey === 0) {
				return 'conflict-ignore'
			} else if (this.event.start > this.option.timestamp + 3599) {
				return 'conflict-no'
			} else if (this.event.end - 1 < this.option.timestamp) {
				return 'conflict-no'
			} else {
				return 'conflict-yes'
			}
		},
	},

	methods: {
		formatDate(timeStamp) {
			return moment.unix(timeStamp).format('LT')
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

	&.conflict-ignore {
		border-left: 4px solid transparent;
		.calendar-info__summay {
			font-weight: bold;
		}
	}

	&.conflict-no {
		border-left: 4px solid var(--color-success);
	}

	&.cancelled {
		text-decoration: line-through;
		opacity: 0.5;
	}

	&.tentative {
		opacity: 0.5;
	}

	&.conflict-yes {
		border-left: 4px solid var(--color-error);
	}
}

.calendar-info__time {
	width: 65px;
	font-size: 80%;
	flex: 0 auto;
}

.calendar-info__summay {
	margin-left: 4px;
}
</style>
