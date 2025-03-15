<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
const props = defineProps({
	title: {
		type: String,
		default: 'Polls',
	},
	fillColor: {
		type: String,
		default: 'currentColor',
	},
	size: {
		type: Number,
		default: 24,
	},
})
</script>

<template>
	<span
		:aria-hidden="!props.title"
		:aria-label="props.title"
		class="material-design-icon polls-app-icon"
		role="img">
		<svg
			:fill="props.fillColor"
			class="material-design-icon__svg"
			:width="props.size"
			:height="props.size"
			viewBox="0 0 32 32">
			<g :fill="props.fillColor">
				<rect y="2" x="3" height="26" width="7" />
				<rect y="12" x="12" height="16" width="7" />
				<rect y="8" x="21" height="20" width="7" />
				<title v-if="props.title">{{ props.title }}</title>
			</g>
		</svg>
	</span>
</template>
