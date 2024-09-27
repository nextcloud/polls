<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref } from 'vue'
	import { NcModal, NcButton } from '@nextcloud/vue'
	import PublicRegisterModal from '../../Public/PublicRegisterModal.vue'
	import { t } from '@nextcloud/l10n'
	import { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js';

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
