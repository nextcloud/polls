<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Component :is="tag" class="spacer">
		<slot />
	</Component>
</template>

<script>
export default {
	name: 'FlexSpacer',
	props: {
		tag: {
			type: String,
			default: 'div',
		},
	},
}
</script>

<style lang="scss">
	.spacer {
		flex: 1;
	}
</style>
