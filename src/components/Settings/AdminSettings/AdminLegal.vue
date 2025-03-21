<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { InputDiv } from '../../Base/index.js'
	import { t } from '@nextcloud/l10n'
	import { useAppSettingsStore } from '../../../stores/appSettings.ts'
	import { computed } from 'vue';

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
		return { privacy, imprint }
	})

</script>

<template>
	<div class="user_settings">
		<p class="settings-description">
			{{ t('polls', 'If you use different legal terms and privacy policy for public polls, enter the links below. Leave empty to use your default terms.') }}
		</p>

		<InputDiv v-model="appSettingsStore.privacyUrl"
			type="url"
			:placeholder="placeholder.privacy"
			:label="t('polls', 'Privacy policy link:')"
			@change="appSettingsStore.write()" />

		<InputDiv v-model="appSettingsStore.imprintUrl"
			type="url"
			inputmode="url"
			:label="t('polls', 'Legal terms link:')"
			:placeholder="placeholder.imprint"
			@change="appSettingsStore.write()" />
	</div>
</template>
