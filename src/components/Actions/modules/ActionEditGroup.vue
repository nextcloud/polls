<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'

import { useSessionStore } from '../../../stores/session'

import { t } from '@nextcloud/l10n'

import ButtonModal from '../../Base/modules/ButtonModal.vue'
import { ButtonMode } from '../../../Types'
import PollGroupEditDlg from '../../PollGroup/PollGroupEditDlg.vue'

import EditIcon from 'vue-material-design-icons/Pencil.vue'

interface Props {
	caption?: string
	modalSize?: string
	buttonMode?: ButtonMode
}

const {
	caption = t('polls', 'Edit poll group'),
	modalSize = 'normal',
	buttonMode = ButtonMode.Native,
} = defineProps<Props>()

const sessionStore = useSessionStore()

const showModal = ref(false)

function updatedGroup() {
	// close modal and show the confirmation dialog
	showModal.value = false
}
</script>

<template>
	<ButtonModal
		v-if="sessionStore.appPermissions.pollCreation"
		v-model:show-modal="showModal"
		:aria-label="caption"
		:button-caption="caption"
		:modal-size="modalSize"
		:button-mode="buttonMode"
		:button-variant="'secondary'">
		<template #icon>
			<EditIcon size="20" decorative />
		</template>
		<template #modal-content>
			<PollGroupEditDlg @updated="updatedGroup" @close="showModal = false" />
		</template>
	</ButtonModal>
</template>
