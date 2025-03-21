<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { MaybeIcon } from '../AppIcons/index.js'
import { Answer } from '../../Types/index.ts'

const props = defineProps({
	answer: {
		type: String,
		default: '',
	},
	active: {
		type: Boolean,
		default: false,
	},
})

const emit = defineEmits(['click'])
const iconSize = 31
const colorCodeNo = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-error',
)
const colorCodeYes = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-success',
)
const colorCodeMaybe = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-warning',
)

const foregroundColor = computed(() => {
	if (props.answer === Answer.Yes) {
		return colorCodeYes
	}
	if (props.answer === Answer.Maybe) {
		return colorCodeMaybe
	}
	return colorCodeNo
})

/**
 *
 */
function onClick() {
	if (props.active) {
		emit('click')
	}
}
</script>

<template>
	<div :class="['vote-indicator', active]" @click="onClick()">
		<MaybeIcon v-if="answer === Answer.Maybe" :size="iconSize" />
		<CheckIcon
			v-if="answer === Answer.Yes"
			:fill-color="foregroundColor"
			:size="iconSize" />
		<CloseIcon
			v-if="answer === Answer.No"
			:fill-color="foregroundColor"
			:size="iconSize" />
	</div>
</template>

<style lang="scss">
.vote-indicator {
	display: flex;
	justify-content: center;
	align-content: end;
	color: var(--color-polls-foreground-no);
	width: 30px;
	height: 30px;

	&,
	* {
		transition: all 0.4s ease-in-out;
		.active & {
			cursor: pointer;
		}
	}

	.active & {
		border: 2px solid;
		border-radius: var(--border-radius);
		.material-design-icon {
			width: 26px;
			height: 26px;
		}
	}
	.yes & {
		color: var(--color-polls-foreground-yes);
	}

	.maybe & {
		color: var(--color-polls-foreground-maybe);
	}

	.active:hover & {
		width: 35px;
		height: 35px;
		.material-design-icon {
			width: 31px;
			height: 31px;
		}
	}
}
</style>
