<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span :aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon spinner"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg viewBox="0 0 100 100"
			:width="size"
			:height="size">
			<circle cx="50"
				cy="50"
				r="45"
				:stroke="fillColor" />
		</svg>
	</span>
</template>

<script>
export default {
	name: 'Spinner',
	props: {
		title: {
			type: String,
			default: 'Admin Access',
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
		size: {
			type: Number,
			default: 24,
		},
	},
}
</script>

<style land="css" scoped>
svg {
  animation: 2s linear infinite svg-animation;
  max-width: 100px;
}

@keyframes svg-animation {
	0% {
		transform: rotateZ(0deg);
	}

	100% {
		transform: rotateZ(360deg)
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
