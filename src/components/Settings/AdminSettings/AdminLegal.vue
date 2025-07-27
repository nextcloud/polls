<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import InputDiv from '../../Base/modules/InputDiv.vue'

import { useAppSettingsStore } from '../../../stores/appSettings'

const appSettingsStore = useAppSettingsStore()
const placeholder = computed(() => {
	let privacy = t('polls', 'Enter the URL of your privacy policy')
	let imprint = t('polls', 'Enter the URL of your legal notice')
	if (appSettingsStore.defaultPrivacyUrl) {
		privacy = appSettingsStore.defaultPrivacyUrl
	}
	if (appSettingsStore.defaultImprintUrl) {
		imprint = appSettingsStore.defaultImprintUrl
	}
	return {
		privacy,
		imprint,
	}
})
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.useSiteLegalTerms"
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{
				t(
					'polls',
					'Use the default terms for public polls and enable the default footer',
				)
			}}
		</NcCheckboxRadioSwitch>
	</div>
	<div v-if="!appSettingsStore.useSiteLegalTerms" class="user_settings">
		<p class="settings-description">
			{{
				t(
					'polls',
					'If you want to use different terms for public polls, enter them below.',
				)
			}}
		</p>

		<InputDiv
			v-model="appSettingsStore.privacyUrl"
			type="url"
			:placeholder="placeholder.privacy"
			:label="t('polls', 'Privacy policy link:')"
			@change="appSettingsStore.write()" />

		<InputDiv
			v-model="appSettingsStore.imprintUrl"
			type="url"
			inputmode="url"
			:label="t('polls', 'Legal terms link:')"
			:placeholder="placeholder.imprint"
			@change="appSettingsStore.write()" />
	</div>
</template>
