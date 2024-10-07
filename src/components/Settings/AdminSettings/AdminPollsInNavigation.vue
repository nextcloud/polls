<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { t } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

	import { useAppSettingsStore } from '../../../stores/appSettings.ts'

	const { appSettingsStore } = useAppSettingsStore()

</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch v-model="appSettingsStore.navigationPollsInList" 
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Load polls into the navigation.') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>
