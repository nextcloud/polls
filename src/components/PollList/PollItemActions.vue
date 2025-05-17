<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import { usePollsStore } from '../../stores/polls.ts'
import { useSessionStore } from '../../stores/session.ts'
import { Poll } from '../../stores/poll.ts'
import { computed, ref } from 'vue'

import { NcDialog } from '@nextcloud/vue'
import DeletePollDialog from '../Modals/DeletePollDialog.vue'
import TransferPollDialog from '../Modals/TransferPollDialog.vue'

import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import TransferPollIcon from 'vue-material-design-icons/AccountSwitchOutline.vue'
import { ButtonVariant } from '@nextcloud/vue/components/NcButton'

const { poll } = defineProps<{ poll: Poll }>()

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()

const adminAccess = computed(
	() => !poll.permissions.view && sessionStore.currentUser.isAdmin,
)

const showDeleteDialog = ref(false)
const showTransferDialog = ref(false)

const showTakeOverDialog = ref(false)
const takeOverDialog = {
	name: t('polls', 'Take over poll?'),
	message: t(
		'polls',
		'You will become the new owner and {username} will get notified.',
		{ username: poll.owner.displayName },
	),

	buttons: [
		{ label: t('polls', 'Cancel') },
		{
			label: t('polls', 'Ok'),
			variant: 'primary' as ButtonVariant,
			callback: () => {
				takeOverPoll()
			},
		},
	],
}

async function toggleArchive() {
	try {
		await pollsStore.toggleArchive({ pollId: poll.id })
	} catch {
		showError(t('polls', 'Error archiving/restoring poll.'))
	}
}

async function clonePoll() {
	try {
		await pollsStore.clone({ pollId: poll.id })
	} catch {
		showError(t('polls', 'Error cloning poll.'))
	}
}

async function takeOverPoll(): Promise<void> {
	if (!sessionStore.currentUser.isAdmin) {
		return
	}

	try {
		await pollsStore.takeOver({ pollId: poll.id })
	} catch {
		showError(t('polls', 'Error taking over poll.'))
	}
}
</script>

<template>
	<NcActions force-menu>
		<NcActionButton
			v-if="poll.permissions.view && sessionStore.appPermissions.pollCreation"
			:name="t('polls', 'Clone poll')"
			:aria-label="t('polls', 'Clone poll')"
			close-after-click
			@click="clonePoll()">
			<template #icon>
				<ClonePollIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="(adminAccess || poll.permissions.edit) && !poll.status.isArchived"
			:name="t('polls', 'Archive poll')"
			:aria-label="t('polls', 'Archive poll')"
			close-after-click
			@click="toggleArchive()">
			<template #icon>
				<ArchivePollIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="(adminAccess || poll.permissions.edit) && poll.status.isArchived"
			:name="t('polls', 'Restore poll')"
			:aria-label="t('polls', 'Restore poll')"
			close-after-click
			@click="toggleArchive()">
			<template #icon>
				<RestorePollIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="adminAccess"
			:name="t('polls', 'Take over')"
			:aria-label="t('polls', 'Take over')"
			close-after-click
			@click="showTakeOverDialog = true">
			<template #icon>
				<PlusIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="adminAccess || poll.permissions.edit"
			class="danger"
			:name="t('polls', 'Delete poll')"
			:aria-label="t('polls', 'Delete poll')"
			close-after-click
			@click="showDeleteDialog = true">
			<template #icon>
				<DeletePollIcon />
			</template>
		</NcActionButton>
		<NcActionButton
			v-if="adminAccess || poll.permissions.edit"
			class="danger"
			:name="t('polls', 'Transfer poll ownership')"
			:aria-label="t('polls', 'Transfer poll ownership')"
			close-after-click
			@click="showTransferDialog = true">
			<template #icon>
				<TransferPollIcon />
			</template>
		</NcActionButton>
	</NcActions>

	<NcDialog v-model:open="showTakeOverDialog" v-bind="takeOverDialog" />

	<TransferPollDialog
		v-model="showTransferDialog"
		:poll="poll"
		@close="showTransferDialog = false" />

	<DeletePollDialog
		v-model="showDeleteDialog"
		:poll="poll"
		@close="showDeleteDialog = false" />
</template>
