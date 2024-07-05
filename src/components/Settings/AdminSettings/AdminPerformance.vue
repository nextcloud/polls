<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<RadioGroupDiv v-model="appSettingsStore.updateType" 
			:options="updateTypeOptions" 
			@update="appSettingsStore.write()" />
	</div>
</template>

<script setup>
import { RadioGroupDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

const appSettingsStore = useAppSettingsStore()
const updateTypeOptions = [
	{ value: 'longPolling', label: t('polls', 'Enable "long polling" for instant updates') },
	{ value: 'periodicPolling', label: t('polls', 'Enable periodic requests of poll updates from the client') },
	{ value: 'noPolling', label: t('polls', 'Disable automatic updates (poll must be reloaded to get updates)') },
]

</script>
