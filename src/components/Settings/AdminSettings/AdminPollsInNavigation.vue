<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.navigationPollsInList" 
			type="switch"
			@update:checked="appSettingsStore.write()">
			{{ t('polls', 'Load polls into the navigation.') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>

import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

export default {
	name: 'AdminPollsInNavigation',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapStores(useAppSettingsStore),
	},
	
	methods: {
		t,
	},
}
</script>
