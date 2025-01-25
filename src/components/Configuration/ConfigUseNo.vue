<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { t } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

	import { usePollStore } from '../../stores/poll.ts'

	const pollStore = usePollStore()
	const label = t('polls', 'Delete vote when switched to "No"')

</script>

<template>
	<NcCheckboxRadioSwitch v-model="pollStore.configuration.useNo"
		type="switch"
		@update:model-value="pollStore.write()">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>
