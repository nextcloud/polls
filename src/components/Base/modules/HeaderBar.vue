<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'

const clamped = ref(true)

/**
 * Toggles the clamped state
 */
function toggleClamp() {
	clamped.value = !clamped.value
}
</script>

<template>
	<div class="header_bar">
		<div class="header_bar_top">
			<div class="bar_top_left">
				<div :class="['header_title', { clamped }]" @click="toggleClamp()">
					<slot name="title" />
				</div>
				<div class="bar_top_left_sub">
					<slot name="sub" />
				</div>
			</div>
			<div class="bar_top_right">
				<slot name="right" />
			</div>
		</div>
		<div class="header_bar_bottom">
			<slot />
		</div>
	</div>
</template>

<style lang="scss">
.page--scrolled .header_bar_bottom {
	display: none;
}

.header_bar {
	.header_bar_top {
		display: flex;
		flex-wrap: wrap-reverse;
		justify-content: flex-end;
		gap: 8px;
		min-height: 3em;

		.bar_top_left {
			display: flex;
			flex-direction: column;
			flex: 1 180px;
			justify-content: center;
		}

		.bar_top_right {
			display: flex;
			flex: 1;
			justify-content: flex-end;
			gap: 8px;
			flex-wrap: wrap;
		}

		.header_title {
			font-weight: bold;
			font-size: 1em;
			line-height: 1.5em;
			overflow: clip;
			text-overflow: ellipsis;
			display: -webkit-box;
		}

		.sub {
			display: flex;
			flex-wrap: wrap;
		}
		.header_bar_bottom {
			display: flex;
			margin-bottom: 16px;
		}
	}

	[class*='bar_'] {
		flex: 0;
	}
}
</style>
