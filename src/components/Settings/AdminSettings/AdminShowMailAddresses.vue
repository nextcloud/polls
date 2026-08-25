<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
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
			v-model="appSettingsStore.showMailAddresses"
			type="switch"
			@update:modelValue="appSettingsStore.write()">
			{{ t('polls', 'Show email addresses of internal accounts') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.showMailAddresses" class="settings_details">
			<NcSelectUsers
				v-model="appSettingsStore.showMailAddressesGroups"
				:inputLabel="
					t('polls', 'Show only to members of the following groups')
				"
				:options="appSettingsStore.groups"
				multiple
				:loading="appSettingsStore.status.loadingGroups"
				:placeholder="t('polls', 'Leave empty to disable globally.')"
				@update:modelValue="appSettingsStore.write()"
				@search="appSettingsStore.loadGroups" />
		</div>
	</div>
</template>
