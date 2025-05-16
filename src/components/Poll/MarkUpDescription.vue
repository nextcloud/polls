<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { usePollStore } from '../../stores/poll.ts'
const pollStore = usePollStore()
</script>

<template>
	<!-- eslint-disable vue/no-v-html -->
	<div
		ref="desc"
		class="markup-description"
		v-html="pollStore.descriptionMarkUp" />
		<!-- eslint-enable vue/no-v-html -->
</template>

<style lang="scss">
.markup-description {
	padding: 0.5rem;
	margin: 0.25rem;
	overflow: auto;

	* {
		margin: revert;
		font-size: revert;
		text-decoration: revert;
		list-style: inside;
	}
}

.markup-description {
	table {
		border-spacing: 2px;
	}

	thead {
		background-color: var(--color-background-darker);
		color: var(--color-main-text);
	}

	td,
	th {
		padding: 1px 4px;
	}
}
</style>
