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
	<Component :is="tag" class="option-item" :class="{ draggable: isDraggable, confirmed: isConfirmed }">
		<div v-if="isDraggable" class="option-item__handle icon icon-handle" />

		<div v-if="showRank" class="option-item__rank">
			{{ option.rank }}
		</div>

		<div v-if="show === 'textBox'" class="option-item__option--text">
			{{ optionText }}
		</div>

		<div v-if="show === 'dateBox'" v-tooltip.auto="dateLocalFormat" class="option-item__option--datebox">
			<div class="month">
				{{ dateBoxMonth }}
			</div>
			<div class="day">
				{{ dateBoxDay }}
			</div>
			<div class="dow">
				{{ dateBoxDow }}
			</div>
			<div class="time">
				{{ dateBoxTime }}
			</div>
		</div>

		<slot name="actions" />
	</Component>
</template>

<script>
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
		type: {
			type: String,
			required: true,
		},
		display: {
			type: String,
			default: 'textBox',
		},
	},
	computed: {
		isDraggable() {
			return this.draggable
		},
		dateLocalFormat() {
			return moment.unix(this.option.timestamp).format('llll')
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
		optionText() {
			if (this.type === 'datePoll') {
				return this.dateLocalFormat
			} else {
				return this.option.pollOptionText
			}
		},
		show() {
			if (this.type === 'datePoll' && this.display === 'dateBox') {
				return 'dateBox'
			} else if (this.type === 'datePoll' && this.display === 'textBox') {
				return 'dateString'
			} else {
				return 'textBox'
			}
		},
		isConfirmed() {
			return this.option.confirmed
		},
	},
}
</script>

<style lang="scss">
	.option-item {
		display: flex;
		align-items: center;
		padding-left: 8px;
		padding-right: 8px;
		line-height: 2em;
		min-height: 4em;
		overflow: visible;
		white-space: nowrap;

		&:active,
		&:hover {
			transition: var(--background-dark) 0.3s ease;
			background-color: var(--color-background-dark);
		}

	}

	[class*='option-item__option'] {
		display: flex;
		flex: 1;
		opacity: 1;
		white-space: normal;
		padding-right: 4px;
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
		justify-content: center;
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

</style>
