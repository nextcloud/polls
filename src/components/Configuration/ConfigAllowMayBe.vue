<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
const label = t('polls', 'Allow "Maybe" vote')
</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="pollStore.configuration.allowMaybe"
		type="switch"
		@update:model-value="emit('change')">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>
