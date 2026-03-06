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
		<div class="stage" :style="style">
			<slot name="default">
				<div class="inner"></div>
			</slot>
		</div>
	</div>
</template>

<style lang="scss" scoped>
.stage {
	display: grid;
	padding: 0.3rem;
	grid-area: center;
	width: 100%;
	height: 100%;
	background-color: var(--color-main-background);
}

.sticky-left {
	position: sticky;
	inset-inline-start: 0;
}

.sticky-top {
	position: sticky;
	top: 0;
}

.sticky-div.sticky-bottom-shadow::after {
	background: linear-gradient(
		to bottom,
		rgba(var(--color-box-shadow-rgb), 0.3),
		rgba(var(--color-box-shadow-rgb), 0)
	);
	content: '';
	position: absolute;
	width: 100%;
	height: 6px;
	bottom: -6px;
	inset-inline-start: 0px;
	z-index: -1;
}

.sticky-div.sticky-right-shadow::after {
	background: linear-gradient(
		to right,
		rgba(var(--color-box-shadow-rgb), 0.3),
		rgba(var(--color-box-shadow-rgb), 0)
	);
	content: '';
	position: absolute;
	height: 100%;
	width: 6px;
	inset-inline-end: -6px;
	top: 0px;
	z-index: -1;
}
</style>
