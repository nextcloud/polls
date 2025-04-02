<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, PropType, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useSessionStore } from '../../../stores/session'

import { t } from '@nextcloud/l10n'
import { ButtonType } from '@nextcloud/vue/components/NcButton'

import ButtonModal from '../../Base/modules/ButtonModal.vue'
import { ButtonMode } from '../../../Types'
import PollCreateDlg from '../../Create/PollCreateDlg.vue'

import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { NcDialog } from '@nextcloud/vue'

const router = useRouter()
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

const newPoll = ref({
	id: 0,
	title: '',
})

const showModal = ref(false)

/**
 *
 * @param payLoad
 * @param payLoad.id
 * @param payLoad.title
 */
function addedPoll(payLoad: { id: number; title: string }) {
	newPoll.value = payLoad

	// close modal and show the confirmation dialog
	showModal.value = false
	showConfirmationDialog.value = true
}

const confirmationDialogMessage = computed(() =>
	t('polls', '"{pollTitle}" has been successfully created.', {
		pollTitle: newPoll.value.title,
	}),
)
const confirmationDialogName = t('polls', 'Poll created')
const showConfirmationDialog = ref(false)
const confirmationDialogProps = {
	buttons: [
		{
			label: t('polls', 'Add another poll'),
			callback: () => {
				addAnotherPoll()
			},
		},
		{
			label: t('polls', 'Open poll now'),
			type: ButtonType.Primary,
			callback: () => {
				router.push({
					name: 'vote',
					params: { id: newPoll.value.id },
				})
			},
		},
	],
}

/**
 *
 */
function addAnotherPoll() {
	showModal.value = true
	showConfirmationDialog.value = false
}
</script>

<template>
	<ButtonModal
		v-if="sessionStore.appPermissions.pollCreation"
		v-model:show-modal="showModal"
		:button-caption="
			buttonMode === ButtonMode.Navigation ? t('polls', 'New poll') : caption
		"
		:modal-size="modalSize"
		:button-mode="buttonMode"
		:button-type="ButtonType.Primary">
		<template #icon>
			<PlusIcon size="20" decorative />
		</template>
		<template #modal-content>
			<PollCreateDlg @added="addedPoll" @close="showModal = false" />
		</template>
	</ButtonModal>
	<NcDialog
		v-model:open="showConfirmationDialog"
		v-bind="confirmationDialogProps"
		:name="confirmationDialogName"
		:message="confirmationDialogMessage" />
</template>
