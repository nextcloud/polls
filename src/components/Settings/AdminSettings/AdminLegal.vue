<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<p class="settings-description">
			{{ t('polls', 'If you use different legal terms and privacy policy for public polls, enter the links below. Leave empty to use your default terms.') }}
		</p>

		<InputDiv v-model="privacyUrl"
			type="url"
			:placeholder="placeholder.privacy"
			:label="t('polls', 'Privacy policy link:')"
			@change="saveSettings()" />

		<InputDiv v-model="imprintUrl"
			type="url"
			inputmode="url"
			:label="t('polls', 'Legal terms link:')"
			:placeholder="placeholder.imprint"
			@change="saveSettings()" />
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { InputDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminLegal',

	components: {
		InputDiv,
	},

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),

		placeholder() {
			let privacy = t('polls', 'Enter the URL of your privacy policy')
			let imprint = t('polls', 'Enter the URL of your legal notice')
			if (this.appSettings.defaultPrivacyUrl) {
				privacy = this.appSettings.defaultPrivacyUrl
			}
			if (this.appSettings.defaultImprintUrl) {
				imprint = this.appSettings.defaultImprintUrl
			}
			return { privacy, imprint }
		},

		// Add bindings
		privacyUrl: {
			get() {
				return this.appSettings.privacyUrl
			},
			set(value) {
				this.$store.commit('appSettings/set', { privacyUrl: value })
			},
		},

		imprintUrl: {
			get() {
				return this.appSettings.imprintUrl
			},
			set(value) {
				this.$store.commit('appSettings/set', { imprintUrl: value })
			},
		},
	},

	methods: {
		t,
		saveSettings() {
			this.$store.dispatch('appSettings/write')
		},
	},
}
</script>
