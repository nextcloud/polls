<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcTimezonePicker from '@nextcloud/vue/components/NcTimezonePicker'
import { usePollStore } from '../../stores/poll'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
</script>

<template>
	<NcTimezonePicker
		v-model="pollStore.configuration.timezoneName as string"
		@update:modelValue="emit('change')" />
</template>
