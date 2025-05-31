<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { usePollStore } from '../../stores/poll.ts'
import { t } from '@nextcloud/l10n'
const pollStore = usePollStore()
const emit = defineEmits(['change'])
</script>

<template>
	<textarea
		v-model="pollStore.configuration.description"
		class="edit-description"
		@change="emit('change')" />
	<NcCheckboxRadioSwitch
		v-model="pollStore.configuration.collapseDescription"
		type="switch"
		@update:model-value="emit('change')">
		{{ t('polls', 'Collapse long descriptions') }}
	</NcCheckboxRadioSwitch>
</template>

<style lang="scss">
textarea.edit-description {
	width: 99%;
	resize: vertical;
	height: 210px;
}
</style>
