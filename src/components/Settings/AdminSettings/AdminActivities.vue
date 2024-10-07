<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	import { t } from '@nextcloud/l10n'
	import { useAppSettingsStore } from '../../../stores/appSettings.ts'

	const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch v-model="appSettingsStore.useActivity" 
		type="switch"
		@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Enable the tracking of activities with the Activities app') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

