<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch v-model="pollStore.configuration.anonymous" 
		type="switch"
		@update:model-value="pollStore.write()">
		{{ t('polls', 'Anonymous poll') }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigAnonymous',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapStores(usePollStore),
	},

	methods: {
		t,
	},
}
</script>
