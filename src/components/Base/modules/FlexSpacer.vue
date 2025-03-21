<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
const props = defineProps({
	tag: {
		type: String,
		default: 'div',
	},
})
</script>

<template>
	<Component :is="props.tag" class="spacer">
		<slot />
	</Component>
</template>

<style lang="scss">
.spacer {
	flex: 1;
}
</style>
