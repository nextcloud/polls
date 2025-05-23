<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="legalTermsInEmail" type="switch">
			{{ t('polls', 'Add terms links also to the email footer') }}
		</NcCheckboxRadioSwitch>

		<div class="disclaimer_group">
			<div class="grow_title">
				<span>{{ t('polls', 'Additional email disclaimer') }}</span>
				<LanguageMarkdownIcon />
			</div>
			<NcCheckboxRadioSwitch :checked.sync="preview" type="switch">
				{{ t('polls', 'Preview') }}
			</NcCheckboxRadioSwitch>
		</div>
		<textarea v-show="!preview" v-model="disclaimer" @change="saveSettings()" />
		<!-- eslint-disable-next-line vue/no-v-html -->
		<div v-show="preview" class="polls-markdown" v-html="markedDisclaimer" />
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { marked } from 'marked'
import { gfmHeadingId } from 'marked-gfm-heading-id'
import DOMPurify from 'dompurify'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'
import LanguageMarkdownIcon from 'vue-material-design-icons/LanguageMarkdown.vue'

const markedPrefix = {
	prefix: 'disclaimer-',
}

export default {
	name: 'AdminEmail',

	components: {
		NcCheckboxRadioSwitch,
		LanguageMarkdownIcon,
	},

	mixins: [writeValue],

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
			marked.use(gfmHeadingId(markedPrefix))
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
	},
}
</script>

<style lang="scss">
	.disclaimer_group {
		display: flex;
		align-items: center;

		.grow_title {
			display: flex;
			flex-grow: 1;
			margin-inline-end: 12px;

			.material-design-icon {
				margin-inline-start: 4px;
			}
		}
	}
</style>
