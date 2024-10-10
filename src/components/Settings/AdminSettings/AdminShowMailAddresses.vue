<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
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
		<NcCheckboxRadioSwitch v-model="appSettingsStore.showMailAddresses" 
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Show email addresses of internal accounts') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.showMailAddresses" class="settings_details">
			<NcSelect v-model="appSettingsStore.showMailAddressesGroups"
				:input-label="t('polls','Show only to members of the following groups')"
				label="displayName"
				:options="appSettingsStore.groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally.')"
				@update:model-value="appSettingsStore.write()"
				@search="appSettingsStore.loadGroups" />
		</div>
	</div>
</template>
