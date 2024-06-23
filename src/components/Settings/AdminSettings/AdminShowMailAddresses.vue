<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.showMailAddresses" 
			type="switch"
			@update:checked="appSettingsStore.write()">
			{{ t('polls', 'Show email addresses of internal accounts') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.showMailAddresses" class="settings_details">
			<NcSelect v-model="appSettingsStore.showMailAddressesGroups"
				:input-label="t('polls','Show only to members of the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally.')"
				@option:selected="appSettingsStore.write()"
				@search="loadGroups" />
		</div>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { loadGroups } from '../../../mixins/adminSettingsMixin.js'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

export default {
	name: 'AdminShowMailAddresses',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups],

	computed: {
		...mapStores(useAppSettingsStore),
	},
	
	methods: {
		t,
	},
}
</script>
