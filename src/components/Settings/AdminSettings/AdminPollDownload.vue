<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'

import { useAppSettingsStore } from '../../../stores/appSettings'

const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.allowPollDownload"
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Enable the spreadsheet download of polls globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.allowPollDownload" class="settings_details">
			<NcSelect
				v-model="appSettingsStore.pollDownloadGroups"
				:input-label="t('polls', 'Enable only for the following groups')"
				label="displayName"
				:options="appSettingsStore.groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally')"
				@update:model-value="appSettingsStore.write()"
				@search="appSettingsStore.loadGroups" />
		</div>
	</div>
</template>
