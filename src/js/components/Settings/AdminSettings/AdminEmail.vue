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
		<CheckboxRadioSwitch :checked.sync="legalTermsInEmail" type="switch">
			{{ t('polls', 'Add terms links also to the email footer') }}
		</CheckboxRadioSwitch>

		<div class="disclaimer_group">
			<span class="grow_title">{{ t('polls', 'Additional email disclaimer') }}</span>
			<CheckboxRadioSwitch :checked.sync="preview" type="switch">
				{{ t('polls', 'Preview') }}
			</CheckboxRadioSwitch>
		</div>
		<textarea v-show="!preview" v-model="disclaimer" @change="saveSettings()" />
		<!-- eslint-disable-next-line vue/no-v-html -->
		<div v-show="preview" class="polls-markdown" v-html="markedDisclaimer">
			{{ markedDisclaimer }}
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { CheckboxRadioSwitch } from '@nextcloud/vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

export default {
	name: 'AdminEmail',

	components: {
		CheckboxRadioSwitch,
	},

	data() {
		return {
			preview: false,
		}
	},

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),

		markedDisclaimer() {
			marked.setOptions({
				headerPrefix: 'disclaimer-',
			})
			return DOMPurify.sanitize(marked.parse(this.appSettings.disclaimer))
		},

		// Add bindings
		legalTermsInEmail: {
			get() {
				return !!this.appSettings.legalTermsInEmail
			},
			set(value) {
				this.writeValue({ legalTermsInEmail: !!value })
			},
		},
		disclaimer: {
			get() {
				return this.appSettings.disclaimer
			},
			set(value) {
				this.$store.commit('appSettings/set', { disclaimer: value })
			},
		},
	},

	methods: {
		saveSettings() {
			this.$store.dispatch('appSettings/write')
		},

		async writeValue(value) {
			await this.$store.commit('appSettings/set', value)
			this.$store.dispatch('appSettings/write')
		},
	},
}
</script>

<style lang="scss">
	.disclaimer_group {
		display: flex;
		align-items: center;

		span {
			margin-right: 12px;
		}

		.grow_title {
			flex-grow: 1;
		}
	}

	.user_settings {
		padding-top: 16px;
		textarea {
			width: 99%;
			resize: vertical;
			height: 230px;
		}
	}

	.settings_details {
		padding-bottom: 16px;
		margin-left: 36px;
		input, .stretch {
			width: 100%;
		}
	}
</style>
