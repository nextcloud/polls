<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.showLogin" 
			type="switch"
			@change="appSettingsStore.write()">
			{{ t('polls', 'Enable the login option in the registration dialog of public polls') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

export default {
	name: 'AdminSharePublicShowLogin',

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
