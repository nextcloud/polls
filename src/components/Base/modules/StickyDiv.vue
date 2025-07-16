<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
	stickyTop?: boolean
	stickyLeft?: boolean
	activateBottomShadow?: boolean
	activateRightShadow?: boolean
}

const {
	stickyTop = false,
	stickyLeft = false,
	activateBottomShadow = false,
	activateRightShadow = false,
} = defineProps<Props>()

const stickyClass = computed(() => ({
	container: true,
	'sticky-top': stickyTop,
	'sticky-left': stickyLeft,
	'sticky-bottom-shadow': activateBottomShadow,
	'sticky-right-shadow': activateRightShadow,
}))
</script>

<template>
	<div :class="stickyClass">
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
	z-index: 5;
}

.sticky-top {
	--shadow-height: 10px;
	position: sticky;
	top: 0;
	z-index: 4;
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

.sticky-top.sticky-left {
	z-index: 7;
}
</style>
