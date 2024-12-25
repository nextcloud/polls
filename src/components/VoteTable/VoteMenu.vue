<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ActionSortOptions, ActionChangeView } from '../Actions/index.js'
</script>

<template>
	<div class="vote_menu">
		<ActionSortOptions button-mode />
		<ActionChangeView />
	</div>
</template>

<style lang="scss">
.vote_menu {
	display: flex;
	justify-content: flex-end;
	flex: 0;
	background-color: var(--color-background-darker);
	border-radius: var(--border-radius-pill);
	margin: 8px;
}

</style>
