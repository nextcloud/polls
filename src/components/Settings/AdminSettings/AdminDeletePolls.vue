<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { InputDiv } from '../../Base/index.ts'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { useAppSettingsStore } from '../../../stores/appSettings.ts'

const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.autoDelete"
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Enable the automatic deletion of archived polls') }}
		</NcCheckboxRadioSwitch>
		<InputDiv
			v-if="appSettingsStore.autoDelete"
			v-model="appSettingsStore.autoDeleteOffset"
			class="settings_details"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:label="
				t('polls', 'Days after which archived polls should be finally deleted')
			"
			@change="appSettingsStore.write()" />
	</div>
</template>
