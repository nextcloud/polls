<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { IconProps } from './types'

const {
	title = t('polls', 'Loading'),
	fillColor = 'currentColor',
	size = 24,
} = defineProps<IconProps>()
</script>

<template>
	<span
		:aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon spinner"
		role="img">
		<svg viewBox="0 0 100 100" :width="size" :height="size">
			<circle cx="50" cy="50" r="45" :stroke="fillColor" />
		</svg>
	</span>
</template>

<style lang="scss" scoped>
svg {
	animation: 2s linear infinite svg-animation;
	max-width: 100px;
}

@keyframes svg-animation {
	0% {
		transform: rotateZ(0deg);
	}

	100% {
		transform: rotateZ(360deg);
	}
}

circle {
	animation: 1.4s ease-in-out infinite both circle-animation;
	display: block;
	fill: transparent;
	/* stroke: #2f3d4c; */
	stroke-linecap: round;
	stroke-dasharray: 283;
	stroke-dashoffset: 280;
	stroke-width: 10px;
	transform-origin: 50% 50%;
}

@keyframes circle-animation {
	0%,
	25% {
		stroke-dashoffset: 280;
		transform: rotate(0);
	}

	50%,
	75% {
		stroke-dashoffset: 75;
		transform: rotate(45deg);
	}

	100% {
		stroke-dashoffset: 280;
		transform: rotate(360deg);
	}
}
</style>
