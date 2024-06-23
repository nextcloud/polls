<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.allowPollDownload" 
			type="switch"
			@update:checked="appSettingsStore.write()">
			{{ t('polls', 'Enable the spreadsheet download of polls globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.allowPollDownload" class="settings_details">
			<NcSelect v-model="appSettingsStore.pollDownloadGroups"
				:input-label="t('polls','Enable only for the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally')"
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
	name: 'AdminPollDownload',

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
