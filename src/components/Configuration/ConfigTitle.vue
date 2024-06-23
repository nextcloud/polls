<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<InputDiv v-model="pollTitle"
		:signaling-class="checkTitle"
		type="text"
		@change="pollStore.write()" />
</template>

<script>
import { mapStores } from 'pinia'
import { InputDiv } from '../Base/index.js'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigTitle',

	components: {
		InputDiv,
	},

	computed: {
		...mapStores(usePollStore),

		checkTitle() {
			return this.pollStore.configuration.title ? '' : 'error'
		},

		pollTitle: {
			get() {
				return this.pollStore.configuration.title
			},
			set(value) {
				this.pollStore.configuration.title = value
			},
		},
	},
}
</script>
