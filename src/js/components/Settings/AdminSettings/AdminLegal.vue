<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="user_settings">
		<p class="settings-description">
			{{ t('polls', 'The privacy link and the legal notice link are automatically added to the registration dialog of public polls.') }}
			{{ t('polls', 'As a default the links configured in the theming app are used. For public polls these can be overriden by individual terms.') }}
		</p>

		<InputDiv v-model="privacyUrl"
			type="url"
			:placeholder="placeholder.privacy"
			:label="t('polls', 'Privacy policy link:')"
			@change="saveSettings()" />

		<InputDiv v-model="imprintUrl"
			type="url"
			inputmode="url"
			:label="t('polls', 'Legal notice link:')"
			:placeholder="placeholder.imprint"
			@change="saveSettings()" />
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { InputDiv } from '../../Base/index.js'

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
		saveSettings() {
			this.$store.dispatch('appSettings/write')
		},
	},
}
</script>
