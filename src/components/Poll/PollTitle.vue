<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { usePollStore } from '@/stores/poll'

const pollStore = usePollStore()
</script>

<template>
	<h2 class="poll_title">
		{{ pollStore.configuration.title }}
	</h2>
</template>

<style lang="scss" scoped>
.poll_title {
	font-size: 1.5rem;
	font-weight: bold;
	margin: 0;
}
</style>
