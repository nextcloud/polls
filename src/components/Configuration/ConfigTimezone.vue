<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { usePollStore } from '../../stores/poll'
import NcTimezonePicker from '@nextcloud/vue/components/NcTimezonePicker'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
</script>

<template>
	<NcTimezonePicker
		v-model="pollStore.configuration.timezoneName"
		@update:model-value="emit('change')" />
</template>
