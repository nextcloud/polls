<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { usePollStore } from '../../stores/poll.ts'
import { computed } from 'vue'

const pollStore = usePollStore()
const label = t('polls', 'Delete vote when switched to "No"')
const deleteNo = computed({
	get: () => !pollStore.configuration.useNo,
	set(value: boolean) {
		pollStore.configuration.useNo = !value
	},
})

</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="deleteNo"
		type="switch"
		@update:model-value="pollStore.write()">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>
