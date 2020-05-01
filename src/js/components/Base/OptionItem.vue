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
	<Component :is="tag" class="option-item" :class="{ draggable: draggable }">
		<div v-if="draggable" class="option-item__handle icon icon-handle" />

		<div v-if="showOrder" class="option-item__order">
			{{ option.order }}
		</div>

		<div v-if="type==='textPoll'" class="option-item__option--text">
			{{ option.pollOptionText }}
		</div>

		<div v-if="type==='datePoll' && display === 'textBox'" class="option-item__option--date">
			{{ moment.unix(option.timestamp).format('llll') }}
		</div>

		<div v-if="type === 'datePoll' && display === 'dateBox'"
			v-tooltip.auto="moment.unix(option.timestamp).format('llll')"
			class="option-item__option--datebox">
			<div class="month">
				{{ moment.unix(option.timestamp).format('MMM') + " '" + moment.unix(option.timestamp).format('YY') }}
			</div>
			<div class="day">
				{{ moment.unix(option.timestamp).format('Do') }}
			</div>
			<div class="dow">
				{{ moment.unix(option.timestamp).format('ddd') }}
			</div>
			<div class="time">
				{{ moment.unix(option.timestamp).format('LT') }}
			</div>
		</div>

		<slot name="actions" />
	</Component>
</template>

<script>
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
		showOrder: {
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

	.option-item__order {
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
