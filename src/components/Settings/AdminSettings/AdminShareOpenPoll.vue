<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.allowAllAccess" 
			type="switch"
			@update:checked="appSettingsStore.write()">
			{{ t('polls', 'Enable the creation of openly accessible polls globally') }}
		</NcCheckboxRadioSwitch>

		<div v-if="!appSettingsStore.allowAllAccess" class="settings_details">
			<NcSelect v-model="appSettingsStore.allAccessGroups"
				:input-label="t('polls','Enable only for the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally')"
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
	name: 'AdminShareOpenPoll',

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
