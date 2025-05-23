<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="header_bar">
		<div class="header_bar_top">
			<div class="bar_top_left">
				<div :class="['header_title', { 'clamped': clamped }]" @click="toggleClamp()">
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
			<slot name="default" />
		</div>
	</div>
</template>

<script>
export default {
	name: 'HeaderBar',
	data() {
		return {
			clamped: true,
		}
	},
	methods: {
		toggleClamp() {
			this.clamped = !this.clamped
		},
	},
}
</script>

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

		.header_title {
			font-weight: bold;
			font-size: 1em;
			line-height: 1.5em;
			overflow: clip;
			text-overflow: ellipsis;
			display: -webkit-box;
			&.clamped {
				-webkit-line-clamp: 2;
				line-clamp: 2;
				-webkit-box-orient: vertical;
			}
		}
		.sub {
			display: flex;
			flex-wrap: wrap;
		}
		.header_bar_bottom {
			display: flex;
			margin-bottom: 16px;
		}
		.bar_top_right {
			padding-top: 3px;
			padding-inline-end: 44px;
		}
	}

	[class*="bar_"] {
		flex: 0;
	}
}
</style>
