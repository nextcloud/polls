<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action toggle-sidebar">
		<NcButton variant="primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
		<NcModal :show.sync="showRegistration"
			:size="registerModalSize"
			@close="closeRegisterModal()">
			<PublicRegisterModal @close="closeRegisterModal()" />
		</NcModal>
	</div>
</template>

<script>
import { NcModal, NcButton } from '@nextcloud/vue'
import PublicRegisterModal from '../../Public/PublicRegisterModal.vue'

export default {
	name: 'ActionRegister',

	components: {
		NcButton,
		NcModal,
		PublicRegisterModal,
	},

	data() {
		return {
			caption: t('polls', 'Register'),
			showRegistration: false,
			registerModalSize: 'large',
		}
	},

	methods: {
		clickAction() {
			this.showRegistration = true
		},

		closeRegisterModal() {
			this.showRegistration = false
		},
	},
}
</script>
