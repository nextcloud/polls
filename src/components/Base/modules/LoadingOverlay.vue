<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { Spinner } from '../../AppIcons/index.ts'
</script>

<template>
	<div class="loading-overlay">
		<Spinner :size="70" />
	</div>
</template>

<style lang="scss">
.loading-overlay {
	position: absolute;
	left: 0;
	top: 0;
	width: 100vw;
	height: 100vh;
	background: var(--color-main-background);
	opacity: 0.9;
	z-index: 9999;

	.spinner {
		position: fixed;
		left: 50%;
		top: 50%;
	}
}
</style>
