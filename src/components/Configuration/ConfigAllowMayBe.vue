<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { usePollStore } from '../../stores/poll'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
const label = t('polls', 'Allow "Maybe" vote')
</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="pollStore.configuration.allowMaybe"
		type="switch"
		@update:modelValue="emit('change')">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>
