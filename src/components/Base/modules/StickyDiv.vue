<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
	activateBottomShadow?: boolean
	activateRightShadow?: boolean
	stickyTop?: boolean
	stickyLeft?: boolean
	zIndex?: number
}

const {
	activateBottomShadow = false,
	activateRightShadow = false,
	stickyTop = false,
	stickyLeft = false,
	zIndex = undefined,
} = defineProps<Props>()

const style = computed(() => {
	if (zIndex !== undefined && zIndex !== null) {
		return {
			'z-index': zIndex,
		}
	}
	if (stickyTop && stickyLeft) {
		return {
			'z-index': 6,
		}
	}

	if (stickyLeft) {
		return {
			'z-index': 5,
		}
	}

	if (stickyTop) {
		return {
			'z-index': 4,
		}
	}

	return {}
})

const stickyClass = computed(() => ({
	'sticky-top': stickyTop,
	'sticky-left': stickyLeft,
	'sticky-bottom-shadow': activateBottomShadow,
	'sticky-right-shadow': activateRightShadow,
}))
</script>

<template>
	<div :class="['sticky-div', stickyClass]" :style="style">
		<div class="top-left-corner"></div>
		<div class="top"></div>
		<div class="top-right-corner"></div>

		<div class="left"></div>
		<div class="stage center" :style="style">
			<slot name="default">
				<div class="inner"></div>
			</slot>
		</div>
		<div class="right"></div>

		<div class="bottom-right-corner"></div>
		<div class="bottom"></div>
		<div class="bottom-left-corner"></div>
	</div>
</template>

<style lang="scss" scoped>
.sticky-div {
	display: grid;
	grid-template-columns: auto 1fr auto;
	grid-template-rows: auto 1fr auto;
	grid-template-areas:
		'top-left-corner top top-right-corner'
		'left center right'
		'bottom-left-corner bottom bottom-right-corner';
}

.stage {
	padding: 0.3rem;
	grid-area: center;
	width: 100%;
	height: 100%;
	background-color: var(--color-main-background);
}

.top-left-corner {
	grid-area: top-left-corner;
	height: 0;
	width: 0;
}

.top {
	grid-area: top;
	height: 0;
}

.top-right-corner {
	grid-area: top-right-corner;
	height: 0;
	width: 0;
}

.right {
	grid-area: right;
	width: 0;
}

.bottom-right-corner {
	grid-area: bottom-right-corner;
	height: 0;
	width: 0;
}

.bottom {
	grid-area: bottom;
	height: 0;
}

.bottom-left-corner {
	grid-area: bottom-left-corner;
	height: 0;
	width: 0;
}

.left {
	grid-area: left;
	width: 0;
}

.sticky-left {
	position: sticky;
	inset-inline-start: 0;
}

.sticky-top {
	position: sticky;
	top: 0;
}

.bottom-right-corner,
.bottom,
.bottom-left-corner {
	background: linear-gradient(
		to bottom,
		rgba(var(--color-box-shadow-rgb), 0.3),
		rgba(var(--color-box-shadow-rgb), 0)
	);

	transition:
		all var(--animation-slow) linear,
		border 1ms;
	.sticky-bottom-shadow & {
		height: var(--shadow-height);
	}
}
</style>
