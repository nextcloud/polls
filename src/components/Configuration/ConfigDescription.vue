<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<textarea v-model="description"
		class="edit-description"
		@change="$emit('change')" />
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'ConfigDescription',

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		description: {
			get() {
				return this.pollConfiguration.description
			},
			set(value) {
				this.$store.commit('poll/setProperty', { description: value })
				this.$store.commit('poll/setDescriptionSafe', value)
			},
		},
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
