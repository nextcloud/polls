<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ButtonType } from '@nextcloud/vue/components/NcButton'

import ButtonModal from '../../Base/modules/ButtonModal.vue'
import { ButtonMode } from '../../../Types'

import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { PropType, ref } from 'vue'
import CreateDlg from '../../Create/CreateDlg.vue'
import { useSessionStore } from '../../../stores/session'

const sessionStore = useSessionStore()

defineProps({
	caption: {
		type: String,
		default: t('polls', 'Add poll'),
	},
	modalSize: {
		type: String,
		default: 'normal',
	},
	buttonMode: {
		type: String as PropType<ButtonMode>,
		default: ButtonMode.Native,
	},
})

const showModal = ref(false)
</script>

<template>
	<ButtonModal
		v-if="sessionStore.appPermissions.pollCreation"
		v-model:show-modal="showModal"
		:button-caption="buttonMode === ButtonMode.Navigation ? t('polls', 'New poll') : caption"
		:modal-size="modalSize"
		:button-mode="buttonMode"
		:button-type="ButtonType.Primary">
		<template #icon>
			<PlusIcon size="20" decorative />
		</template>
		<template #modal-content>
			<CreateDlg @cancel="showModal = false" />
		</template>
	</ButtonModal>
</template>
