<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch v-model="pollStore.configuration.useNo" 
		type="switch"
		@update:model-value="pollStore.write()">
		{{ label }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigUseNo',

	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			label: t('polls', 'Delete vote when switched to "No"'),
		}
	},

	computed: {
		...mapStores(usePollStore),

	},
}
</script>
