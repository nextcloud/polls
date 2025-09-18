<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
const { tag = 'span' } = defineProps<{ tag?: string }>()
</script>

<template>
	<Component :is="tag" class="badge-small">
		<slot name="icon" />
		<span v-if="$slots.default">
			<slot />
		</span>
	</Component>
</template>

<style scoped lang="scss">
#body-user .badge-small {
	display: flex;
	flex: 0 0 fit-content;
	align-items: center;
	gap: 5px;
	border: 2px solid;
	border-radius: 0.8rem !important;
	text-align: center;
	font-size: 0.9em;
	overflow: hidden;
	padding: 0px 8px !important;
	margin: 0 !important;
	min-height: 1.4rem;

	span {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	h2 & {
		font-size: 0.6em;
	}

	&.error {
		background-color: var(--color-error);
		border-color: var(--color-border-error);
	}

	&.success {
		background-color: var(--color-success);
		border-color: var(--color-border-success);
	}

	&.warning {
		background-color: var(--color-warning);
		border-color: var(--color-element-warning);
	}
}
</style>
