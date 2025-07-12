<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { MaybeIcon } from '../AppIcons/index.ts'
import { Answer } from '../../Types/index.ts'

const ICON_SIZE = 26

interface Props {
	answer: Answer
	active?: boolean
}

const { answer, active = false } = defineProps<Props>()

const emit = defineEmits(['click'])

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
	if (answer === 'yes') {
		return colorCodeYes
	}
	if (answer === 'maybe') {
		return colorCodeMaybe
	}
	return colorCodeNo
})

function onClick() {
	if (active) {
		emit('click')
	}
}
</script>

<template>
	<div :class="['vote-indicator', active]" @click="onClick()">
		<MaybeIcon v-if="answer === 'maybe'" :size="ICON_SIZE" />
		<CheckIcon
			v-if="answer === 'yes'"
			:fill-color="foregroundColor"
			:size="ICON_SIZE" />
		<CloseIcon
			v-if="answer === 'no'"
			:fill-color="foregroundColor"
			:size="ICON_SIZE" />
	</div>
</template>

<style lang="scss">
.vote-indicator {
	color: var(--color-polls-foreground-no);
	min-width: 30px;
	min-height: 30px;

	&,
	* {
		transition: all 0.4s ease-in-out;
		margin: auto;
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
