<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import InputDiv from '../../Base/modules/InputDiv.vue'
import { useAppSettingsStore } from '../../../stores/appSettings'

const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.autoDelete"
			type="switch"
			@update:modelValue="appSettingsStore.write()">
			{{ t('polls', 'Enable the automatic deletion of archived polls') }}
		</NcCheckboxRadioSwitch>
		<InputDiv
			v-if="appSettingsStore.autoDelete"
			v-model="appSettingsStore.autoDeleteOffset"
			class="settings_details"
			type="number"
			inputmode="numeric"
			useNumModifiers
			:label="
				t(
					'polls',
					'Days after which archived polls should be permanently deleted',
				)
			"
			@change="appSettingsStore.write()" />
	</div>
</template>
