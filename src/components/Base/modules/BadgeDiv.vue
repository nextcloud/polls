<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
const { tag = 'span' } = defineProps<{ tag?: string }>()
</script>

<template>
	<Component :is="tag" class="badge">
		<slot name="icon" />
		<span>
			<slot />
		</span>
	</Component>
</template>

<style lang="scss">
.badge {
	display: flex;
	align-items: center;
	gap: 5px;
	border-radius: var(--border-radius-small);
	padding: 5px;
	text-align: center;
	line-height: 1.1em;
	font-size: 0.9em;
	overflow: hidden;

	span {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	h2 & {
		font-size: 0.6em;
	}

	&.error {
		border-color: var(--color-error);
		background-color: var(--color-error);
		color: var(--color-primary-element-text) !important;
	}

	&.success {
		border-color: var(--color-success);
		background-color: var(--color-success);
		color: var(--color-primary-element-text) !important;
	}
}
</style>
