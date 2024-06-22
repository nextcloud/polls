<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.allowPublicShares" 
			type="switch"
			@change="appSettingsStore.write()">
			{{ t('polls', 'Enable public shares of polls globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.allowPublicShares" class="settings_details">
			<NcSelect v-model="appSettingsStore.publicSharesGroups"
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
	name: 'AdminSharePublicCreate',

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
