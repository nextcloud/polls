<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { RadioGroupDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore, UpdateType } from '../../../stores/appSettings.ts'

const appSettingsStore = useAppSettingsStore()
const updateTypeOptions = [
	{
		value: UpdateType.LongPolling,
		label: t('polls', 'Enable "long polling" for instant updates'),
	},
	{
		value: UpdateType.Periodic,
		label: t(
			'polls',
			'Enable periodic requests of poll updates from the client',
		),
	},
	{
		value: UpdateType.NoPolling,
		label: t(
			'polls',
			'Disable automatic updates (poll must be reloaded to get updates)',
		),
	},
]
</script>

<template>
	<div class="user_settings">
		<RadioGroupDiv
			v-model="appSettingsStore.updateType"
			:options="updateTypeOptions"
			@update="appSettingsStore.write()" />
	</div>
</template>
