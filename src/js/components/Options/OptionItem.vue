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
	<Component :is="tag" class="option-item" :class="{ draggable: isDraggable, 'date-box': show === 'dateBox' }">
		<div v-if="isDraggable" class="option-item__handle icon icon-handle" />

		<div v-if="showRank" class="option-item__rank">
			{{ option.rank }}
		</div>

		<div v-if="show === 'textBox'" v-tooltip.auto="optionTooltip" class="option-item__option--text">
			{{ optionText }}
		</div>

		<div v-if="show === 'dateBox'" v-tooltip.auto="dateLocalFormatUTC" class="option-item__option--datebox">
			<div class="event-from">
				<div class="month">
					{{ dateBoxMonth }}
				</div>
				<div class="day">
					{{ dateBoxDay }}
				</div>
				<div class="dow">
					{{ dateBoxDow }}
				</div>
				<div v-if="!oneDayEvent && !wholeDayDuration" class="time">
					{{ dateBoxTime }}
				</div>
			</div>
			<div v-if="option.duration && !oneDayEvent" class="event-to">
				<div v-if="option.duration && !oneDayEvent" class="devider">
					-
				</div>
				<div v-if="option.duration && !oneDayEvent && !sameDayUntil" class="until">
					{{ dateUntil }}
				</div>
				<div v-if="option.duration && !oneDayEvent && !wholeDayDuration" class="until">
					{{ timeUntil }}
				</div>
			</div>
		</div>

		<slot name="actions" />
	</Component>
</template>

<script>
import { mapState } from 'vuex'
import moment from '@nextcloud/moment'
export default {
	name: 'OptionItem',

	props: {
		draggable: {
			type: Boolean,
			default: false,
		},
		option: {
			type: Object,
			required: true,
		},
		showRank: {
			type: Boolean,
			default: false,
		},
		tag: {
			type: String,
			default: 'div',
		},
		display: {
			type: String,
			default: 'textBox',
		},
	},
	computed: {
		...mapState({
			poll: state => state.poll,
		}),

		isDraggable() {
			return this.draggable
		},

		dateLocalFormat() {
			if (this.option.duration) {
				if (this.oneDayEvent) {
					return moment.unix(this.option.timestamp).format('ll')
				} else if (this.wholeDayDuration) {
					return moment.unix(this.option.timestamp).format('ll') + ' - ' + this.dateUntil
				} else if (this.sameDayUntil) {
					return moment.unix(this.option.timestamp).format('llll') + ' - ' + this.timeUntil
				} else {
					return moment.unix(this.option.timestamp).format('llll') + ' - ' + this.dateUntil + ' ' + this.timeUntil
				}
			} else {
				return moment.unix(this.option.timestamp).format('llll')
			}
		},

		dateLocalFormatUTC() {
			if (this.option.duration) {
				return moment.unix(this.option.timestamp).utc().format('llll') + ' - ' + moment.unix(this.option.timestamp).add(this.option.duration, 'seconds').utc().format('llll') + ' UTC'
			} else {
				return moment.unix(this.option.timestamp).utc().format('llll') + ' UTC'
			}
		},

		oneDayEvent() {
			// this event starts at 0:00 local time and lasts exact 24 hours
			return this.option.duration === 86400 && moment.unix(this.option.timestamp).startOf('day').diff(moment.unix(this.option.timestamp), 'seconds') === 0
		},

		wholeDayDuration() {
			// This event starts at 0:00 local time and lasts one or more full days
			return this.option.duration && this.option.duration % 86400 === 0 && moment.unix(this.option.timestamp).startOf('day').diff(moment.unix(this.option.timestamp), 'seconds') === 0
		},

		sameDayUntil() {
			return moment.unix(this.option.timestamp).format('L') === moment.unix(this.option.timestamp).add(Math.max(0, this.option.duration - 1), 'seconds').format('L')
		},

		dateBoxMonth() {
			return moment.unix(this.option.timestamp).format('MMM') + " '" + moment.unix(this.option.timestamp).format('YY')
		},

		dateBoxDay() {
			return moment.unix(this.option.timestamp).format('Do')
		},

		dateBoxDow() {
			return moment.unix(this.option.timestamp).format('ddd')
		},

		dateBoxTime() {
			return moment.unix(this.option.timestamp).format('LT')
		},

		timeUntil() {
			return moment.unix(this.option.timestamp).add(this.option.duration, 'seconds').format('LT')
		},

		dateUntil() {
			return moment.unix(this.option.timestamp).add(Math.max(0, this.option.duration - 1), 'seconds').format('L')
			// return '(+ ' + n('polls', '%n day', '%n days', Math.floor(this.option.duration / 86400)) + ')'
		},

		optionTooltip() {
			if (this.poll.type === 'datePoll') {
				return this.dateLocalFormatUTC
			} else {
				return this.option.pollOptionText
			}
		},

		optionText() {
			if (this.poll.type === 'datePoll') {
				return this.dateLocalFormat
			} else {
				return this.option.pollOptionText
			}
		},

		show() {
			if (this.poll.type === 'datePoll' && this.display === 'dateBox') {
				return 'dateBox'
			} else {
				return 'textBox'
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.option-item {
		display: flex;
		align-items: center;
		&.date-box {
			flex: 1;
			flex-direction: column;
			align-items: flex-start;
		}
	}

	[class*='event'] {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.event-from {
		flex: 0 0 110px;
	}

	.event-to {
		flex: 0 0 75px;
	}

	[class*='option-item__option'] {
		flex: 1;
		opacity: 1;
		white-space: normal;
		padding-right: 4px;
	}

	.option-item__option--text {
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.draggable, .draggable [class*='option-item__option']  {
		cursor: grab;
		&:active {
			cursor: grabbing;
			cursor: -moz-grabbing;
			cursor: -webkit-grabbing;
		}
		.option-item__handle {
			visibility: hidden;
		}
		&:hover > .option-item__handle {
			visibility: visible;
		}

	}

	.option-item__rank {
		flex: 0 0;
		justify-content: flex-end;
		padding-right: 8px;
	}

	.option-item__handle {
		margin-right: 8px;
	}

	.option-item__option--datebox {
		display: flex;
		flex-direction: column;
		padding: 0 2px;
		align-items: center;
		justify-content: flex-start;
		text-align: center;

		.month, .dow {
			font-size: 1.1em;
			color: var(--color-text-lighter);
		}
		.day {
			font-size: 1.4em;
			margin: 5px 0 5px 0;
		}
	}

	.mobile {
		.option-item {
			flex: 2;
			order: 1;
			min-width: 0;
			.option-item__option--text {
				hyphens: auto;
				align-items: center;
				white-space: nowrap;
				width: 50px;
			}
		}
	}
</style>
