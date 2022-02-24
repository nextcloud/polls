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
		<MinusIcon v-if="useNumModifiers" class="modifier subtract" @click="$emit('subtract')" />
		<input ref="input"
			:type="type"
			:value="value"
			:inputmode="inputmode"
			:placeholder="placeholder"
			:class="[{ 'has-modifier': useNumModifiers }, 'input', signalingClass]"
			@input="$emit('input', $event.target.value)"
			@change="$emit('change', $event.target.value)"
			@keyup.enter="$emit('submit', $event.target.value)">
		<PlusIcon v-if="useNumModifiers" class="modifier add" @click="$emit('add')" />
		<ButtonDiv v-if="!useNumModifiers && !noSubmit" submit @click="$emit('submit', $refs.input.value)" />
	</div>
</template>

<script>

import ButtonDiv from '../Base/ButtonDiv'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
export default {
	name: 'InputDiv',

	components: {
		ButtonDiv,
		PlusIcon,
		MinusIcon,
	},

	props: {
		value: {
			type: [String, Number],
			required: true,
		},
		signalingClass: {
			type: String,
			default: '',
		},
		placeholder: {
			type: String,
			default: '',
		},
		type: {
			type: String,
			default: 'text',
			validator(value) {
				return ['text', 'email', 'number', 'url'].includes(value)
			},
		},
		inputmode: {
			type: String,
			default: null,
			validator(value) {
				return ['text', 'none', 'numeric', 'email', 'url'].includes(value)
			},
		},
		useNumModifiers: {
			type: Boolean,
			default: false,
		},
		focus: {
			type: Boolean,
			default: false,
		},
		noSubmit: {
			type: Boolean,
			default: false,
		},
	},

	mounted() {
		if (this.focus) {
			this.setFocus()
		}
	},

	methods: {
		setFocus() {
			this.$nextTick(() => {
				this.$refs.input.focus()
			})
		},
	},
}

</script>

<style lang="scss">

	.input-div {
		position: relative;
		display: flex;

		input {
			width: 100%;
			background-repeat: no-repeat;
			background-position: right 12px center;

			&:empty:before {
				color: grey;
			}

			&.has-modifier {
				padding: 0 34px;
			}

			&.error {
				border-color: var(--color-error);
				background-color: var(--color-background-error);
				background-image: var(--icon-polls-no);
				color: var(--color-foreground-error);
			}

			&.checking {
				border-color: var(--color-warning);
				background-image: var(--icon-polls-loading);
			}

			&.success, &.icon-confirm.success {
				border-color: var(--color-success);
				background-image: var(--icon-polls-yes);
				background-color: var(--color-background-success) !important;
				color: var(--color-foreground-success);
			}
		}

		&.numeric {
			min-width: 100px;
			width: 110px;
			display: block;

			input {
				text-align: center;
			}
		}

		.modifier {
			flex: 0;
			position: absolute;
			top: 0;
			height: 32px;
			margin: 4px 1px;
			padding: 0 4px;
			border-color: var(--color-border-dark);
			cursor: pointer;

			&:hover {
				background-color: var(--color-background-hover)
			}

			&.add {
				right: 0;
				border-left: solid 1px var(--color-border-dark);
				border-radius: 0 var(--border-radius) var(--border-radius) 0;
			}

			&.subtract {
				left: 0;
				border-right: solid 1px var(--color-border-dark);
				border-radius: var(--border-radius) 0 0 var(--border-radius);
			}
		}
	}
</style>
