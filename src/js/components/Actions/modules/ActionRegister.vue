<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<div class="action toggle-sidebar">
		<NcButton type="primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
		<NcModal :show.sync="showRegistration"
			:size="registerModalSize"
			:can-close="true"
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
