<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcCheckboxRadioSwitch :checked.sync="pollStore.configuration.allowComment" 
		type="switch"
		@update:checked="pollStore.write()">
		{{ t('polls', 'Allow Comments') }}
	</NcCheckboxRadioSwitch>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigAllowComment',

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
