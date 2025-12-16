<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import AlertIcon from 'vue-material-design-icons/AlertCircleOutline.vue'
import ChevronLeftIcon from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import Spinner from '../../AppIcons/Spinner.vue'
import { Logger } from '../../../helpers/modules/logger'

import { SignalingType } from '../../../Types'
import NcButton from '@nextcloud/vue/components/NcButton'

import { t } from '@nextcloud/l10n'

interface Props {
	signalingClass?: SignalingType
	placeholder?: string
	type?: 'text' | 'email' | 'number' | 'url'
	inputmode?: 'text' | 'none' | 'numeric' | 'email' | 'url'
	useNumModifiers?: boolean
	modifierStepValue?: number
	numMax?: number
	numMin?: number
	numWrap?: boolean
	focus?: boolean
	submit?: boolean
	helperText?: string
	label?: string
	useNumericVariant?: boolean
	disabled?: boolean
}
const model = defineModel<string | number>({ required: true })

const vInputFocus = {
	mounted: (el: { focus: () => void }) => {
		if (focus) {
			el.focus()
		}
	},
}

const {
	signalingClass = '',
	placeholder = '',
	type = 'text',
	inputmode,
	useNumModifiers = false,
	modifierStepValue = 1,
	numMax,
	numMin,
	numWrap = false,
	focus = false,
	submit = false,
	helperText = null,
	label = null,
	useNumericVariant = false,
	disabled = false,
} = defineProps<Props>()

const emit = defineEmits(['input', 'change', 'submit'])

const numericModelValue = computed(() =>
	typeof model.value === 'number' ? model.value : parseInt(model.value),
)

const computedSignalingClass = computed(() => {
	if (signalingClass === 'valid') {
		return 'success'
	}
	if (signalingClass === 'invalid') {
		return 'error'
	}
	if (signalingClass === 'missing') {
		return 'error'
	}
	return signalingClass
})

const checking = computed(
	() => !useNumModifiers && computedSignalingClass.value === 'checking',
)
const error = computed(
	() =>
		!checking.value
		&& !useNumModifiers
		&& computedSignalingClass.value === 'error',
)
const success = computed(
	() =>
		!checking.value
		&& !useNumModifiers
		&& computedSignalingClass.value === 'success'
		&& !submit,
)
const showSubmit = computed(
	() =>
		!checking.value
		&& !useNumModifiers
		&& submit
		&& computedSignalingClass.value !== 'error',
)

/**
 * Check if numMin is less than numMax, if both are set
 * Returns false in case of failed validation and just logs a warning
 */
function assertBoundaries() {
	if (numMin && numMax && numMin >= numMax) {
		Logger.warn(
			'numMin is greater or equal than numMax. Validation will be skipped.',
		)
		return false
	}
	return true
}

/**
 * Check if value is within numMin and numMax
 *
 * @param value Value to be checked
 * @return value kept within defined boundaries
 */
function numCheckBoundaries(value: number) {
	if (numMax && value > numMax) {
		if (
			numWrap
			&& numMin
			&& assertBoundaries()
			&& numericModelValue.value === numMax
		) {
			value = numMin
		} else {
			value = numMax
		}
	}

	if (numMin && value < numMin) {
		if (
			numWrap
			&& numMax
			&& assertBoundaries()
			&& numericModelValue.value === numMin
		) {
			value = numMax
		} else {
			value = numMin
		}
	}

	return value
}

/**
 *  Add modifierStepValue to value
 */
function add() {
	const nextValue = numCheckBoundaries(numericModelValue.value + modifierStepValue)

	if (model.value !== nextValue) {
		model.value = nextValue
		emit('change')
	}
}

/**
 * Subtract modifierStepValue from value respecting wrapping and boundaries
 * emits 'change' event if model.value has changed
 */
function subtract() {
	const nextValue = numCheckBoundaries(numericModelValue.value - modifierStepValue)

	if (model.value !== nextValue) {
		model.value = nextValue
		emit('change')
	}
}

onMounted(() => {
	assertBoundaries()
})
const componentClass = computed(() => [
	'input-div',
	{ numeric: useNumModifiers || inputmode === 'numeric' },
])

const inputClass = computed(() => [
	{
		'has-modifier': useNumModifiers && useNumericVariant,
		'has-submit': submit,
	},
	computedSignalingClass.value,
])
</script>

<template>
	<div :class="componentClass">
		<label v-if="label">
			{{ label }}
		</label>

		<div class="input-wrapper">
			<NcButton
				v-if="useNumModifiers && !useNumericVariant"
				class="date-add-button"
				:title="t('polls', 'minus')"
				:variant="'tertiary-no-background'"
				@click="subtract">
				<template #icon>
					<ChevronLeftIcon />
				</template>
			</NcButton>

			<input
				v-model="model"
				v-input-focus
				:disabled="disabled"
				:type="type"
				:inputmode="inputmode"
				:placeholder="placeholder"
				:class="inputClass"
				@input="emit('input')"
				@change="emit('change')"
				@keyup.enter="emit('submit')" />

			<Spinner v-if="checking" class="signaling-icon spinner" />
			<AlertIcon v-else-if="error" class="signaling-icon error" />
			<CheckIcon v-else-if="success" class="signaling-icon success" />
			<ArrowRightIcon
				v-else-if="showSubmit"
				class="signaling-icon submit"
				@click="emit('submit')" />
			<NcButton
				v-if="useNumModifiers && !useNumericVariant"
				:title="t('polls', 'plus')"
				:variant="'tertiary-no-background'"
				@click="add">
				<template #icon>
					<ChevronRightIcon />
				</template>
			</NcButton>
			<MinusIcon
				v-if="useNumModifiers && useNumericVariant"
				class="modifier subtract"
				@click="subtract()" />
			<PlusIcon
				v-if="useNumModifiers && useNumericVariant"
				class="modifier add"
				@click="add()" />
		</div>

		<div v-if="helperText !== null" :class="['helper', computedSignalingClass]">
			{{ helperText }}
		</div>
	</div>
</template>

<style lang="scss" scoped>
.input-div {
	--input-height: 44px;
	position: relative;
	margin-bottom: var(--default-grid-baseline);
	display: block !important;

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
			padding-inline-end: var(--input-height);
		}

		&.has-modifier {
			padding: 0 var(--input-height);
		}

		&.error {
			border-color: var(--color-border-error);
		}

		&.success {
			border-color: var(--color-border-success);
		}
	}

	.input-wrapper {
		position: relative;
		display: flex;
		& > input {
			flex: 1;
		}
	}

	.helper {
		min-height: 1.5rem;
		font-size: 0.8em;
		opacity: 0.8;
		&.error {
			opacity: 1;
			color: var(--color-text-error);
		}
	}

	&.numeric .input-wrapper {
		input {
			text-align: center;
			max-width: 4rem;
			padding: 0;
		}
	}

	.signaling-icon {
		position: absolute;
		inset-inline-end: 0;
		width: var(--input-height);
		height: var(--input-height);
	}

	.modifier {
		position: absolute;
		height: 100%;
		width: var(--input-height);
		border-color: var(--color-border-dark);
		cursor: pointer;

		&:hover {
			background-color: var(--color-background-hover);
		}

		&.add {
			inset-inline-end: 0;
			border-inline-start: solid 2px var(--color-border-maxcontrast);
			border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
		}

		&.subtract {
			border-inline-end: solid 2px var(--color-border-maxcontrast);
			border-radius: var(--border-radius-small) 0 0 var(--border-radius-small);
		}
	}
}
</style>
