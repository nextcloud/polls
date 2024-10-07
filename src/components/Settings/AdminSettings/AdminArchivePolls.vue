<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { InputDiv } from '../../Base/index.js'
	import { t } from '@nextcloud/l10n'
	
	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	
	import { useAppSettingsStore } from '../../../stores/appSettings.ts'

	const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch v-model="appSettingsStore.autoArchive" type="switch"
		@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Enable the automatic poll archiving') }}
		</NcCheckboxRadioSwitch>
		<InputDiv v-if="appSettingsStore.autoArchive"
			v-model="appSettingsStore.autoArchiveOffset"
			class="settings_details"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:label="t('polls', 'Days after which polls should be archived after closing')" 
			@change="appSettingsStore.write()"/>
	</div>
</template>

