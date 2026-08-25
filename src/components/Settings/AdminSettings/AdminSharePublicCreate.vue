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
			v-model="appSettingsStore.allowPublicShares"
			type="switch"
			@update:modelValue="appSettingsStore.write()">
			{{ t('polls', 'Enable public shares of polls globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.allowPublicShares" class="settings_details">
			<NcSelectUsers
				v-model="appSettingsStore.publicSharesGroups"
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
