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
		<h3 v-if="label">
			{{ label }}
		</h3>
		<div class="input-wrapper">
			<input ref="input"
				:type="type"
				:value="value"
				:inputmode="inputmode"
				:placeholder="placeholder"
				:class="[{ 'has-modifier': useNumModifiers, 'has-submit': !noSubmit }, 'input', signalingClass]"
				@input="$emit('input', $event.target.value)"
				@change="$emit('change', $event.target.value)"
				@keyup.enter="$emit('submit', $event.target.value)">
			<Spinner v-if="checking" class="signaling-icon spinner" />
			<ArrowRight v-if="showSubmit" class="signaling-icon submit" @click="$emit('submit', $refs.input.value)" />
			<AlertIcon v-if="error" class="signaling-icon error" fill-color="#f45573" />
			<CheckIcon v-if="success" class="signaling-icon success" fill-color="#49bc49" />
			<MinusIcon v-if="showModifiers" class="modifier subtract" @click="$emit('subtract')" />
			<PlusIcon v-if="showModifiers" class="modifier add" @click="$emit('add')" />
		</div>
		<div v-if="helperText!==null" :class="['helper', signalingClass]">
			{{ helperText }}
		</div>
	</div>
</template>

<script>
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import AlertIcon from 'vue-material-design-icons/AlertCircle.vue'
import Spinner from '../AppIcons/Spinner.vue'

export default {
	name: 'InputDiv',

	components: {
		ArrowRight,
		PlusIcon,
		MinusIcon,
		AlertIcon,
		CheckIcon,
		Spinner,
	},

	props: {
		value: {
			type: [String, Number],
			required: true,
		},
		signalingClass: {
			type: String,
			default: '',
			validator(value) {
				return ['', 'empty', 'error', 'success', 'checking'].includes(value)
			},
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
		helperText: {
			type: String,
			default: null,
		},
		label: {
			type: String,
			default: null,
		},
	},

	computed: {
		error() {
			return !this.checking && !this.useNumModifiers && this.signalingClass === 'error'
		},
		success() {
			return !this.checking && !this.useNumModifiers && this.signalingClass === 'success' && this.noSubmit
		},
		showSubmit() {
			return !this.checking && !this.useNumModifiers && !this.noSubmit && this.signalingClass !== 'error'
		},
		showModifiers() {
			return this.useNumModifiers
		},
		checking() {
			return !this.useNumModifiers && this.signalingClass === 'checking'
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

<style lang="scss" scoped>

	.input-div {
		position: relative;

		.input-wrapper {
			position: relative;
		}

		.helper {
			min-height: 1.5rem;
			font-size: 0.8em;
			opacity: 0.8;
			&.error {
				opacity: 1;
				color: var(--color-error)
			}
		}

		input {
			width: 100%;

			&.has-submit {
				padding-right: 34px;
			}

			&.has-modifier {
				padding: 0 34px;
			}

			&.error {
				border-color: var(--color-error);
				background-color: var(--color-background-error);
				color: var(--color-foreground-error);
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

		.signaling-icon {
			&.material-design-icon {
				position: absolute;
				right: 6px;
				top: 8px;
			}
		}

		.submit {
			position: absolute;
			right: 6px;
			top: 8px;
			cursor: pointer;
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
