<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { t } from '@nextcloud/l10n'
	
	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

	import { useAppSettingsStore } from '../../../stores/appSettings.ts'

	const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch v-model="appSettingsStore.allowPollCreation" 
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Enable the poll creation globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.allowPollCreation" class="settings_details">
			<NcSelect v-model="appSettingsStore.pollCreationGroups"
				:input-label="t('polls','Enable only for the following groups')"
				label="displayName"
				:options="appSettingsStore.groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally')"
				@option:selected="appSettingsStore.write()"
				@search="appSettingsStore.loadGroups" />
		</div>
	</div>
</template>
