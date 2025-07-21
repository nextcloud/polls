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
	container: true,
	'sticky-top': stickyTop,
	'sticky-left': stickyLeft,
	'sticky-bottom-shadow': activateBottomShadow,
	'sticky-right-shadow': activateRightShadow,
}))
</script>

<template>
	<div :class="stickyClass" :style="style">
		<slot name="default">
			<div class="inner"></div>
		</slot>
	</div>
</template>

<style lang="scss" scoped>
.container {
	--shadow-height: 10px;
}

.inner {
	width: 100%;
	height: 100%;
	background-color: var(--color-main-background);
}

.sticky-left {
	position: sticky;
	left: 0;
}

.sticky-top {
	--shadow-height: 10px;
	position: sticky;
	top: 0;
	padding-bottom: 0px;
	padding-bottom: var(--shadow-height);

	&::after {
		content: '';
		position: absolute;
		bottom: 0;
		left: -1px;
		right: 0;
		height: 0;
		background: linear-gradient(
			to bottom,
			rgba(var(--color-box-shadow-rgb), 0.3),
			rgba(var(--color-box-shadow-rgb), 0)
		);
		transition:
			all var(--animation-slow) linear,
			border 1ms;
	}

	&.sticky-bottom-shadow {
		border-top: 0;
		padding-bottom: var(--shadow-height);
		margin-bottom: 0;
		&::after {
			height: var(--shadow-height);
		}
	}
}

/* TODO: Implement sticky right shadow
	An Alternative could be using a grid instead of ::after
	to be able to position multiple shadows in all directions */

/*
	padding-right: var(--shadow-height);

	&::after {
		content: '';
		position: absolute;
		right: 0;
		top: -1px;
		bottom: 0;
		width: 0;
		background: linear-gradient(
			to right,
			rgba(var(--color-box-shadow-rgb), 0.3),
			rgba(var(--color-box-shadow-rgb), 0)
		);
		transition:
			all var(--animation-slow) linear,
			border 1ms;
	}

	&.sticky-right-shadow {
		border-right: 0;
		padding-right: var(--shadow-height);
		margin-right: 0;
		&::after {
			width: var(--shadow-height);
		}
	} */
</style>
