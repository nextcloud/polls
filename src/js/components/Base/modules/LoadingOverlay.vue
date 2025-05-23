<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="loading-overlay">
		<Spinner :size="70" />
	</div>
</template>

<script>
import { Spinner } from '../../AppIcons/index.js'

export default {
	name: 'LoadingOverlay',
	components: {
		Spinner,
	},
}
</script>

<style lang="scss">
.loading-overlay {
	position: absolute;
	inset-inline-start: 0;
	top: 0;
	width: 100vw;
	height: 100vh;
	background: var(--color-main-background);
	opacity: 0.9;
	z-index: 9999;

	.spinner {
		position: fixed;
		inset-inline-start: 50%;
		top: 50%;
	}
}
</style>
