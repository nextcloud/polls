<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

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

<script>
import { mapStores } from 'pinia'
import { InputDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

export default {
	name: 'AdminLegal',

	components: {
		InputDiv,
	},

	computed: {
		...mapStores(useAppSettingsStore),

		placeholder() {
			let privacy = t('polls', 'Enter the URL of your privacy policy')
			let imprint = t('polls', 'Enter the URL of your legal notice')
			if (this.appSettingsStore.defaultPrivacyUrl) {
				privacy = this.appSettingsStore.defaultPrivacyUrl
			}
			if (this.appSettingsStore.defaultImprintUrl) {
				imprint = this.appSettingsStore.defaultImprintUrl
			}
			return { privacy, imprint }
		},
	},

	methods: {
		t,
	},
}
</script>
