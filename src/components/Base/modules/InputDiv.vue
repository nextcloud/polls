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
				@input="emitValidated('input', $event.target.value)"
				@change="emitValidated('change', $event.target.value)"
				@keyup.enter="emitValidated('submit'), $event.target.value">

			<Spinner v-if="checking" class="signaling-icon spinner" />
			<AlertIcon v-else-if="error" class="signaling-icon error" />
			<CheckIcon v-else-if="success" class="signaling-icon success" />
			<ArrowRightIcon v-else-if="showSubmit" class="signaling-icon submit" @click="emitValidated('submit', $event.target.value)" />
			<MinusIcon v-if="useNumModifiers" class="modifier subtract" @click="subtract()" />
			<PlusIcon v-if="useNumModifiers" class="modifier add" @click="add()" />
		</div>

		<div v-if="helperText!==null" :class="['helper', computedSignalingClass]">
			{{ helperText }}
		</div>
	</div>
</template>

<script>
import { defineComponent } from 'vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import AlertIcon from 'vue-material-design-icons/AlertCircleOutline.vue'
import { Spinner } from '../../AppIcons/index.js'
import { Logger } from '../../../helpers/index.js'

export default defineComponent({
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
		numMax: {
			type: Number,
			default: null,
		},
		numMin: {
			type: Number,
			default: null,
		},
		numWrap: {
			type: Boolean,
			default: false,
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

		isNumMinSet() {
			return this.numMin !== null
		},

		isNumMaxSet() {
			return this.numMax !== null
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
		this.assertBoundaries()
	},

	methods: {
		setFocus() {
			this.$nextTick(() => {
				this.$refs.input.focus()
			})
		},

		assertBoundaries() {
			if (this.isNumMinSet && this.isNumMaxSet && this.numMin >= this.numMax) {
				Logger.warning('numMin is greater or equal than numMax. Validation will be skipped.')
				return false
			}
			return true
		},	

		add() {
			const value = this.numWrapper(this.value + this.modifierStepValue)
			if (value !== this.value) {
				this.emitValidated('change', value)
			}
		},

		subtract() {
			const value = this.numWrapper(this.value - this.modifierStepValue)
			if (value !== this.value) {
				this.emitValidated('change', value)
			}
		},

		numWrapper(value) {
			if (!this.assertBoundaries() || (!this.isNumMaxSet && !this.isNumMinSet)) {	
				this.$emit('input', value)
				return value
			}	

			if (this.isNumMaxSet && value > this.numMax) {
				if (this.numWrap) {
					value = this.numMin ?? 0
				} else {
					value = this.numMax
				}
			} 

			if (this.isNumMinSet && value < this.numMin) {
				if (this.numWrap) {
					value = this.numMax ?? value
				} else {
					value = this.numMin
				}
			}

			this.$emit('input', value)
			return value
		},

		numCheckBoundaries(value) {
			if (this.type === 'number' && (this.isNumMinSet || this.isNumMaxSet)) {
				if (this.isNumMaxSet && value > this.numMax) {
					value = this.numMax
				}

				if (this.isNumMinSet && value < this.numMin) {
					value = this.numMin
				}
			}

			return value
		},

		emitValidated(eventName = 'input', value) {
			if (eventName === 'change') {
				value = this.numCheckBoundaries(value)
			}

			this.$emit(eventName, value)
		},
	},
})

</script>

<style lang="scss" scoped>
	$input-height: 44px;

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
				padding-right: $input-height;
			}

			&.has-modifier {
				padding: 0 $input-height;
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
			right: 0;
			width: $input-height;
			height: $input-height;
		}

		.modifier {
			position: absolute;
			height: 100%;
			width: $input-height;
			border-color: var(--color-border-dark);
			cursor: pointer;

			&:hover {
				background-color: var(--color-background-hover)
			}

			&.add {
				right: 0;
				border-left: solid 2px var(--color-border-maxcontrast);
				border-radius: 0 var(--border-radius) var(--border-radius) 0;
			}

			&.subtract {
				border-right: solid 2px var(--color-border-maxcontrast);
				border-radius: var(--border-radius) 0 0 var(--border-radius);
			}
		}
	}
</style>
