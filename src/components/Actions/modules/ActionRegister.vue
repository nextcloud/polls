<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	// '@nextcloud/vue/dist/Components/.js'
	// /dist/Components/.js
	import { ref } from 'vue'
	import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	import PublicRegisterModal from '../../Public/PublicRegisterModal.vue'
	import { t } from '@nextcloud/l10n'

	const caption = t('polls', 'Register')
	const showRegistration = ref(false)
	const registerModalSize = 'large'

</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton :type="ButtonType.Primary"
			:aria-label="caption"
			@click="showRegistration = true">
			{{ caption }}
		</NcButton>
		<NcModal v-model:show="showRegistration"
			:size="registerModalSize"
			:can-close="true"
			@close="showRegistration = false">
			<PublicRegisterModal @close="showRegistration = false" />
		</NcModal>
	</div>
</template>
