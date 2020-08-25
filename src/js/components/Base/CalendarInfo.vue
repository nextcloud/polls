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
		:class="conflictLevel"
		:style="{ backgroundColor: event.displayColor, color: fontColor }">
		<div class="calendar-info__time">
			{{ formatDate(event.eventFrom) }} - {{ formatDate(event.eventTo) }}
		</div>
		<div class="calendar-info__summay">
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
		fontColor() {
			if (this.event.displayColor === 'transparent') {
				return 'black'
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

			return l > 0.5 ? 'black' : 'white'
		},

		conflictLevel() {
			if (this.event.key === 0) {
				return 'conflict-ignore'
			} else if (this.event.eventFrom >= this.option.timestamp + 3600) {
				return 'conflict-no'
			} else if (this.event.eventTo <= this.option.timestamp) {
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
	}

	&.conflict-no {
		border-left: 4px solid var(--color-success);
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

</style>
