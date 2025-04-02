<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

import { usePollsStore } from '../../stores/polls.ts'
import { useSessionStore } from '../../stores/session.ts'
import { Poll } from '../../stores/poll.ts'
import { computed, PropType, ref } from 'vue'
import { ButtonType } from '@nextcloud/vue/components/NcButton'
import { NcDialog } from '@nextcloud/vue'

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()

const props = defineProps({
	poll: {
		type: Object as PropType<Poll>,
		required: true,
	},
})

const adminAccess = computed(
	() => !props.poll.permissions.view && sessionStore.currentUser.isAdmin,
)

const showDeleteDialog = ref(false)
const deleteDialog = {
	name: t('polls', 'Delete poll'),
	buttons: [
		{ label: t('polls', 'Cancel') },
		{
			label: t('polls', 'OK'),
			type: ButtonType.Primary,
			callback: () => {
				deletePoll()
			},
		},
	],
}

const showTakeOverDialog = ref(false)
const takeOverDialog = {
	name: t('polls', 'Take over poll?'),
	message: t(
		'polls',
		'You will become the new owner and {username} will get notified.',
		{ username: props.poll.owner.displayName },
	),

	buttons: [
		{ label: t('polls', 'Cancel') },
		{
			label: t('polls', 'Ok'),
			type: ButtonType.Primary,
			callback: () => {
				takeOverPoll()
			},
		},
	],
}

/**
 *
 */
async function toggleArchive() {
	try {
		await pollsStore.toggleArchive({ pollId: props.poll.id })
	} catch {
		showError(t('polls', 'Error archiving/restoring poll.'))
	}
}

/**
 *
 */
async function deletePoll() {
	try {
		await pollsStore.delete({ pollId: props.poll.id })
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}

/**
 *
 */
async function clonePoll() {
	try {
		await pollsStore.clone({ pollId: props.poll.id })
	} catch {
		showError(t('polls', 'Error cloning poll.'))
	}
}

/**
 *
 */
async function takeOverPoll(): Promise<void> {
	if (!sessionStore.currentUser.isAdmin) {
		return
	}

	try {
		await pollsStore.takeOver({ pollId: props.poll.id })
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
			v-if="(adminAccess || poll.permissions.edit) && !poll.status.isDeleted"
			:name="t('polls', 'Archive poll')"
			:aria-label="t('polls', 'Archive poll')"
			close-after-click
			@click="toggleArchive()">
			<template #icon>
				<ArchivePollIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="(adminAccess || poll.permissions.edit) && poll.status.isDeleted"
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
			v-if="adminAccess || (poll.permissions.edit && poll.status.isDeleted)"
			class="danger"
			:name="t('polls', 'Delete poll')"
			:aria-label="t('polls', 'Delete poll')"
			close-after-click
			@click="showDeleteDialog = true">
			<template #icon>
				<DeletePollIcon />
			</template>
		</NcActionButton>
	</NcActions>

	<NcDialog v-model:open="showTakeOverDialog" v-bind="takeOverDialog" />

	<NcDialog v-model:open="showDeleteDialog" v-bind="deleteDialog">
		<span v-if="adminAccess">
			{{
				t(
					'polls',
					'This will finally delete the poll and {username} will get notified.',
					{ username: props.poll.owner.displayName },
				)
			}}
		</span>
		<span v-else>
			{{ t('polls', 'This will finally delete the poll.') }}
		</span>
	</NcDialog>
</template>
