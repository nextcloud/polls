<!--
  - @copyright Copyright (c) 2022 René Gieling <github@dartcafe.de>
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
