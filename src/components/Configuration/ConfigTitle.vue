<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { InputDiv } from '../Base/index.js'
import { usePollStore } from '../../stores/poll.ts'

const pollStore = usePollStore()
const checkTitle = computed(() => (pollStore.configuration.title ? '' : 'error'))
const pollTitle = computed({
	get: () => pollStore.configuration.title,
	set: (value) => {
		pollStore.configuration.title = value
	},
})
</script>

<template>
	<InputDiv
		v-model="pollTitle"
		:signaling-class="checkTitle"
		type="text"
		@change="pollStore.write()" />
</template>
