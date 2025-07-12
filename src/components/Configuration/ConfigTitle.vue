<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { InputDiv } from '../Base/index.ts'
import { usePollStore } from '../../stores/poll.ts'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
const checkTitle = computed(() => (pollStore.configuration.title ? '' : 'error'))
</script>

<template>
	<InputDiv
		v-model="pollStore.configuration.title"
		:signaling-class="checkTitle"
		type="text"
		@change="emit('change')" />
</template>
