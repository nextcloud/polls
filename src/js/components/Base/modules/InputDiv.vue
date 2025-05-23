<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="['input-div', { numeric: useNumModifiers }]">
		<label v-if="label">
			{{ label }}
		</label>

		<div class="input-wrapper">
			<input ref="input"
				:type="type"
				:value="value"
				:inputmode="inputmode"
				:placeholder="placeholder"
				:class="[{ 'has-modifier': useNumModifiers, 'has-submit': submit }, computedSignalingClass]"
				@input="$emit('input', $event.target.value)"
				@change="$emit('change', $event.target.value)"
				@keyup.enter="$emit('submit', $event.target.value)">

			<Spinner v-if="checking" class="signaling-icon spinner" />
			<ArrowRightIcon v-if="showSubmit" class="signaling-icon submit" @click="$emit('submit', $refs.input.value)" />
			<AlertIcon v-if="error" class="signaling-icon error" />
			<CheckIcon v-if="success" class="signaling-icon success" />
			<MinusIcon v-if="useNumModifiers" class="modifier subtract" @click="subtract()" />
			<PlusIcon v-if="useNumModifiers" class="modifier add" @click="add()" />
		</div>

		<div v-if="helperText!==null" :class="['helper', computedSignalingClass]">
			{{ helperText }}
		</div>
	</div>
</template>

<script>
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import AlertIcon from 'vue-material-design-icons/AlertCircleOutline.vue'
import { Spinner } from '../../AppIcons/index.js'

export default {
	name: 'InputDiv',

	components: {
		ArrowRightIcon,
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
				return ['', 'empty', 'error', 'valid', 'invalid', 'success', 'checking'].includes(value)
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
		modifierStepValue: {
			type: Number,
			default: 1,
		},
		modifierMax: {
			type: Number,
			default: null,
		},
		modifierMin: {
			type: Number,
			default: null,
		},
		focus: {
			type: Boolean,
			default: false,
		},
		submit: {
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
		computedSignalingClass() {
			if (this.signalingClass === 'valid') return 'success'
			if (this.signalingClass === 'invalid') return 'error'
			return this.signalingClass
		},

		error() {
			return !this.checking && !this.useNumModifiers && this.computedSignalingClass === 'error'
		},
		success() {
			return !this.checking && !this.useNumModifiers && this.computedSignalingClass === 'success' && !this.submit
		},
		showSubmit() {
			return !this.checking && !this.useNumModifiers && this.submit && this.computedSignalingClass !== 'error'
		},
		checking() {
			return !this.useNumModifiers && this.computedSignalingClass === 'checking'
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

		add() {
			let newValue = this.value
			if (this.modifierMax && (newValue + this.modifierStepValue) > this.modifierMax) {
				if (this.modifierMin) {
					newValue = this.modifierMin
				}
			} else {
				newValue += this.modifierStepValue
			}
			this.$emit('input', newValue)
		},

		subtract() {
			let newValue = this.value
			if (this.modifierMin && (newValue - this.modifierStepValue) < this.modifierMin) {
				if (this.modifierMax) {
					newValue = this.modifierMax
				}
			} else {
				newValue -= this.modifierStepValue
			}
			this.$emit('input', newValue)
		},
	},
}

</script>

<style lang="scss" scoped>
	.input-div {
		position: relative;
		flex: 1;

		label {
			display: block;
			margin-bottom: 2px;
		}

		input {
			margin: 0;

			&.has-submit,
			&.error,
			&.success,
			&.checking {
				padding-inline-end: 44px;
			}

			&.has-modifier {
				padding: 0 44px;
			}

			&.error {
				border-color: var(--color-error);
			}

			&.success {
				border-color: var(--color-success);
			}
		}

		.input-wrapper {
			position: relative;
			display: flex;
			& > input {
				height: 44px !important;
				flex: 1;
			}
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

		&.numeric .input-wrapper {
			min-width: 110px;
			max-width: 150px;
			input {
				text-align: center;
			}
		}

		.signaling-icon {
			position: absolute;
			inset-inline-end: 0;
			width: 44px;
			height: 44px;
		}

		.modifier {
			position: absolute;
			height: 100%;
			width: 44px;
			border-color: var(--color-border-dark);
			cursor: pointer;

			&:hover {
				background-color: var(--color-background-hover)
			}

			&.add {
				inset-inline-end: 0;
				border-inline-start: solid 2px var(--color-border-maxcontrast);
				border-radius: 0 var(--border-radius) var(--border-radius) 0;
			}

			&.subtract {
				border-inline-end: solid 2px var(--color-border-maxcontrast);
				border-radius: var(--border-radius) 0 0 var(--border-radius);
			}
		}
	}
</style>
