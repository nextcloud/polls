<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { usePollStore } from '../../stores/poll.ts'
const pollStore = usePollStore()
</script>

<template>
	<textarea
		v-model="pollStore.configuration.description"
		class="edit-description"
		@change="pollStore.write()" />
</template>

<style lang="scss">
textarea.edit-description {
	width: 99%;
	resize: vertical;
	height: 210px;
}
</style>
