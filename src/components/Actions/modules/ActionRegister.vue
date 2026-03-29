<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'

import NcModal from '../../Base/modules/CustomNcModal.vue'
import PublicRegisterModal from '../../Public/PublicRegisterModal.vue'
import { usePollStore } from '@/stores/poll'

const caption = t('polls', 'Register')
const showRegistration = ref(true)
const registerModalSize = 'large'
const pollStore = usePollStore()
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
			:name="pollStore.configuration.title"
			:size="registerModalSize"
			close-on-click-outside
			@close="showRegistration = false">
			<PublicRegisterModal @close="showRegistration = false" />
		</NcModal>
	</div>
</template>
