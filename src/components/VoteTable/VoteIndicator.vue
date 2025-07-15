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

const { answer } = defineProps<{ answer: Answer }>()

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
</script>

<template>
	<div class="vote-indicator">
		<MaybeIcon
			v-if="answer === 'maybe'"
			:fill-color="foregroundColor"
			:size="ICON_SIZE" />
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
.active .vote-indicator {
	border: 2px solid;
	border-radius: var(--border-radius);

	&,
	* {
		cursor: pointer;
	}
	.material-design-icon {
		width: 26px;
		height: 26px;
	}

	&:hover {
		width: 35px;
		height: 35px;
		.material-design-icon {
			width: 31px;
			height: 31px;
		}
	}
}

.vote-indicator {
	color: var(--color-polls-foreground-no);
	min-width: 30px;
	min-height: 30px;
	display: flex;
	align-items: center;
	justify-content: center;

	&,
	* {
		transition: all 0.4s ease-in-out;
	}

	.yes & {
		color: var(--color-polls-foreground-yes);
	}

	.maybe & {
		color: var(--color-polls-foreground-maybe);
	}
}
</style>
