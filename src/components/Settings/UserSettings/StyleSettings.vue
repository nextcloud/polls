<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	import { t } from '@nextcloud/l10n'
	import { usePreferencesStore } from '../../../stores/preferences.ts'
	const preferencesStore = usePreferencesStore()
</script>

<template>
	<div>
		<b> {{ t('polls', 'The style settings are still experimental!') }}</b>
		<div class="user_settings">
			<NcCheckboxRadioSwitch v-model="preferencesStore.user.useCommentsAlternativeStyling" 
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Use alternative styling for the comments sidebar') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch v-model="preferencesStore.user.useAlternativeStyling" 
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Use alternative vote page styling') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>
