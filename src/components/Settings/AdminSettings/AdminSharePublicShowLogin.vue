<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { useAppSettingsStore } from '../../../stores/appSettings.ts'

const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.showLogin"
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{
				t(
					'polls',
					'Enable the login option in the registration dialog of public polls',
				)
			}}
		</NcCheckboxRadioSwitch>
	</div>
</template>
