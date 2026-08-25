<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.allowPollCreation"
			type="switch"
			@update:modelValue="appSettingsStore.write()">
			{{ t('polls', 'Enable the poll creation globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.allowPollCreation" class="settings_details">
			<NcSelectUsers
				v-model="appSettingsStore.pollCreationGroups"
				:inputLabel="t('polls', 'Enable only for the following groups')"
				:options="appSettingsStore.groups"
				multiple
				:loading="appSettingsStore.status.loadingGroups"
				:placeholder="t('polls', 'Leave empty to disable globally')"
				@update:modelValue="appSettingsStore.write()"
				@search="appSettingsStore.loadGroups" />
		</div>
	</div>
</template>
