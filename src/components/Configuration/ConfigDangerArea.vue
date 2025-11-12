<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/components/NcButton'

import { usePollStore } from '../../stores/poll'

import DeletePollDialog from '../Modals/DeletePollDialog.vue'
import RestorePollIcon from 'vue-material-design-icons/RecycleVariant.vue'
import ArchivePollIcon from 'vue-material-design-icons/ArchiveOutline.vue'
import DeletePollIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import TransferPollIcon from 'vue-material-design-icons/AccountSwitchOutline.vue'
import TransferPollDialog from '../Modals/TransferPollDialog.vue'

const router = useRouter()
const pollStore = usePollStore()
const showDeleteDialog = ref(false)
const showTransferDialog = ref(false)

/**
 *
 */
function toggleArchive() {
	try {
		pollStore.toggleArchive({ pollId: pollStore.id })
	} catch {
		showError(
			t('polls', 'Error {action} poll.', {
				action: pollStore.status.isArchived ? 'restoring' : 'archiving',
			}),
		)
	}
}

function routeAway() {
	router.push({
		name: 'list',
		params: {
			type: 'relevant',
		},
	})
}
</script>

<template>
	<div class="delete-area">
		<NcButton @click="toggleArchive()">
			<template #icon>
				<RestorePollIcon v-if="pollStore.status.isArchived" />
				<ArchivePollIcon v-else />
			</template>
			<template #default>
				{{
					pollStore.status.isArchived
						? t('polls', 'Restore poll')
						: t('polls', 'Archive poll')
				}}
			</template>
		</NcButton>

		<NcButton :variant="'error'" @click="showDeleteDialog = true">
			<template #icon>
				<DeletePollIcon />
			</template>
			<template #default>
				{{ t('polls', 'Delete poll') }}
			</template>
		</NcButton>
		<NcButton @click="showTransferDialog = true">
			<template #icon>
				<TransferPollIcon />
			</template>
			<template #default>
				{{ t('polls', 'Transfer poll') }}
			</template>
		</NcButton>
	</div>
	<DeletePollDialog
		v-model="showDeleteDialog"
		:poll="pollStore"
		@deleted="routeAway"
		@close="showDeleteDialog = false" />
	<TransferPollDialog
		v-model="showTransferDialog"
		:poll="pollStore"
		@access-denied="routeAway"
		@close="showTransferDialog = false" />
</template>

<style lang="scss">
.delete-area {
	display: grid;
	gap: 8px;
	grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
}
</style>
