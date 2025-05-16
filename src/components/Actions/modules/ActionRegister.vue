<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'

import PublicRegisterModal from '../../Public/PublicRegisterModal.vue'

const caption = t('polls', 'Register')
const showRegistration = ref(false)
const registerModalSize = 'large'
</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton
			:variant="'primary'"
			:aria-label="caption"
			@click="showRegistration = true">
			{{ caption }}
		</NcButton>
		<NcModal
			v-model:show="showRegistration"
			:name="caption"
			:size="registerModalSize"
			close-on-click-outside
			@close="showRegistration = false">
			<PublicRegisterModal @close="showRegistration = false" />
		</NcModal>
	</div>
</template>
