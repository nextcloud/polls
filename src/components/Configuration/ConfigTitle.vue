<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<InputDiv v-model="pollTitle"
		:signaling-class="checkTitle"
		type="text"
		@change="$emit('change')" />
</template>

<script>
import { mapState } from 'vuex'
import { InputDiv } from '../Base/index.js'

export default {
	name: 'ConfigTitle',

	components: {
		InputDiv,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		checkTitle() {
			return this.pollConfiguration.title ? '' : 'error'
		},

		pollTitle: {
			get() {
				return this.pollConfiguration.title
			},
			set(value) {
				this.$store.commit('poll/setProperty', { title: value })
			},
		},
	},
}
</script>
