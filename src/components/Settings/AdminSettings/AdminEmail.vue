<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { computed, ref } from 'vue'
import { marked } from 'marked'
import { gfmHeadingId } from 'marked-gfm-heading-id'
import DOMPurify from 'dompurify'
import LanguageMarkdownIcon from 'vue-material-design-icons/LanguageMarkdownOutline.vue'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { useAppSettingsStore } from '../../../stores/appSettings'

const appSettingsStore = useAppSettingsStore()

const markedPrefix = {
	prefix: 'disclaimer-',
}

const preview = ref(false)
const markedDisclaimer = computed(() => {
	marked.use(gfmHeadingId(markedPrefix))
	return DOMPurify.sanitize(marked.parse(appSettingsStore.disclaimer))
})
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.legalTermsInEmail"
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Add terms links also to the email footer') }}
		</NcCheckboxRadioSwitch>

		<div class="disclaimer_group">
			<div class="grow_title">
				<span>{{ t('polls', 'Additional email disclaimer') }}</span>
				<LanguageMarkdownIcon />
			</div>
			<NcCheckboxRadioSwitch
				v-model="preview"
				type="switch"
				@change="appSettingsStore.write()">
				{{ t('polls', 'Preview') }}
			</NcCheckboxRadioSwitch>
		</div>
		<textarea
			v-show="!preview"
			v-model="appSettingsStore.disclaimer"
			@change="appSettingsStore.write()" />
		<!-- eslint-disable-next-line vue/no-v-html -->
		<div v-show="preview" class="polls-markdown" v-html="markedDisclaimer" />
	</div>
</template>

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
