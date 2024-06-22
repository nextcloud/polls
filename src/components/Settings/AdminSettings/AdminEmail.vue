<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.legalTermsInEmail" 
			type="switch"
			@change="appSettingsStore.write()">

			{{ t('polls', 'Add terms links also to the email footer') }}
		</NcCheckboxRadioSwitch>

		<div class="disclaimer_group">
			<div class="grow_title">
				<span>{{ t('polls', 'Additional email disclaimer') }}</span>
				<LanguageMarkdownIcon />
			</div>
			<NcCheckboxRadioSwitch :checked.sync="preview" 
				type="switch"
				@change="appSettingsStore.write()">
				{{ t('polls', 'Preview') }}
			</NcCheckboxRadioSwitch>
		</div>
		<textarea v-show="!preview" v-model="appSettingsStore.disclaimer" @change="appSettingsStore.write()" />
		<!-- eslint-disable-next-line vue/no-v-html -->
		<div v-show="preview" class="polls-markdown" v-html="markedDisclaimer" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { marked } from 'marked'
import { gfmHeadingId } from 'marked-gfm-heading-id'
import DOMPurify from 'dompurify'
import LanguageMarkdownIcon from 'vue-material-design-icons/LanguageMarkdown.vue'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

const markedPrefix = {
	prefix: 'disclaimer-',
}

export default {
	name: 'AdminEmail',

	components: {
		NcCheckboxRadioSwitch,
		LanguageMarkdownIcon,
	},

	data() {
		return {
			preview: false,
		}
	},

	computed: {
		...mapStores(useAppSettingsStore),

		markedDisclaimer() {
			marked.use(gfmHeadingId(markedPrefix))
			return DOMPurify.sanitize(marked.parse(this.appSettingsStore.disclaimer))
		},
	},

	methods: {
		t,
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
			margin-right: 12px;

			.material-design-icon {
				margin-left: 4px;
			}
		}
	}
</style>
