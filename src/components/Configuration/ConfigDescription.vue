<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<textarea v-model="pollStore.configuration.description"
		class="edit-description"
		@change="pollStore.write()" />
</template>

<script>
import { mapStores } from 'pinia'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigDescription',

	computed: {
		...mapStores(usePollStore),
	},
}
</script>

<style lang="scss">
	textarea.edit-description {
		width: 99%;
		resize: vertical;
		height: 210px;
	}
</style>
