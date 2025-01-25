<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { t } from '@nextcloud/l10n'
	import { usePollStore } from '../../stores/poll.ts'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

	const pollStore = usePollStore()
</script>

<template>
	<NcCheckboxRadioSwitch v-model="pollStore.configuration.allowComment"
		type="switch"
		@update:model-value="pollStore.write()">
		{{ t('polls', 'Allow Comments') }}
	</NcCheckboxRadioSwitch>
</template>
