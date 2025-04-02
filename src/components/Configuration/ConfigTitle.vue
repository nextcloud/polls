<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { InputDiv } from '../Base/index.ts'
import { usePollStore } from '../../stores/poll.ts'
import { SignalingType } from '../../Types/index.ts'

const pollStore = usePollStore()
const checkTitle = computed(() =>
	pollStore.configuration.title ? SignalingType.None : SignalingType.Error,
)
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
