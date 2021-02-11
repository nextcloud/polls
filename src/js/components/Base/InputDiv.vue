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

<template lang="html">
	<div :class="['input-div', { numeric: useNumModifiers }]">
		<div v-if="useNumModifiers" class="modifyer substract icon icon-polls-minus" @click="$emit('substract')" />
		<input ref="input"
			:value="value"
			:placeholder="placeholder"
			class="input"
			@keyup.enter="$emit('input', $event.target.value)">
		<ButtonDiv v-if="!useNumModifiers && !noSubmit" submit @click="$emit('input', $refs.input.value)" />
		<div v-if="useNumModifiers" class="modifyer add icon icon-add" @click="$emit('add')" />
	</div>
</template>

<script>

import ButtonDiv from '../Base/ButtonDiv'

export default {
	name: 'InputDiv',

	components: {
		ButtonDiv,
	},

	props: {
		value: {
			type: [String, Number],
			required: true,
		},
		placeholder: {
			type: String,
			default: '',
		},
		useNumModifiers: {
			type: Boolean,
			default: false,
		},
		noSubmit: {
			type: Boolean,
			default: false,
		},
	},
}

</script>

<style lang="scss" scoped>

	.input-div {
		position: relative;
		display: flex;
	}

	.input-div.numeric {
		min-width: 100px;
		width: 110px;
		display: block;
	}

	.numeric input {
		text-align: center;
	}

	input {
		width: 100%;
		&:empty:before {
			color: grey;
		}
	}

	.add {
		right: 0;
		border-left: solid 1px;
		border-radius: 0 var(--border-radius) var(--border-radius) 0;
	}

	.substract {
		left: 0;
		border-right: solid 1px;
		border-radius: var(--border-radius) 0 0 var(--border-radius);
	}

	.modifyer {
		position: absolute;
		top: 0;
		height: 32px;
		margin: 4px 1px;
		padding: 0 14px;
		border-color: var(--color-border-dark);
		cursor: pointer;
		&:hover {
			background-color: var(--color-background-hover)
		}
	}

</style>
