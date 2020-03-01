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
	<li class="poll-item text" :class="{ draggable: draggable }">
		<IconBase v-if="draggable" icon-name="handle" class="handle">
			<IconHandle />
		</IconBase>

		<div v-if="showOrder" class="order">
			{{ option.order }}
		</div>

		<div class="pollOption">
			{{ option.pollOptionText }}
		</div>
		<slot name="actions" />
	</li>
</template>

<script>
import IconBase from '../Icons/IconBase.vue'
import IconHandle from '../Icons/IconHandle.vue'

export default {
	name: 'PollItemText',

	components: {
		IconBase,
		IconHandle
	},

	props: {
		option: {
			type: Object,
			required: true
		},

		showOrder: {
			type: Boolean,
			default: false
		},

		draggable: {
			type: Boolean,
			default: false
		}
	}
}
</script>

<style lang="scss">
	.poll-item {
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

		> div {
			display: flex;
			flex: 1;
			font-size: 1.2em;
			opacity: 1;
			white-space: normal;
			padding-right: 4px;
			&.avatar {
				flex: 0;
			}
		}

		.order {
			flex: 0 0;
			justify-content: flex-end;
			padding-right: 8px;
		}

		.handle {
			margin-right: 8px;
		}

		.action {
			justify-content: center;
			flex: 0 0;
		}
	}
</style>
