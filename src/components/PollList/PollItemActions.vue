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

import { NcActionInput, NcDialog } from '@nextcloud/vue'
import DeletePollDialog from '../Modals/DeletePollDialog.vue'
import TransferPollDialog from '../Modals/TransferPollDialog.vue'

import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import IconArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
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
const subMenu = ref<'addToGroup' | 'removeFromGroup' | null>(null)

const newGroupTitle = ref('')

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

async function toggleSubMenu(
	action: 'addToGroup' | 'removeFromGroup' | null = null,
) {
	subMenu.value = subMenu.value === action ? null : action
}

async function removePollFromGroup(pollId: number, pollGroupId: number) {
	subMenu.value = null
	try {
		pollsStore.removePollFromGroup({
			pollId,
			pollGroupId,
		})
	} catch {
		showError(t('polls', 'Error removing poll from group.'))
	}
}

async function addPollToPollGroup(pollId: number, pollGroupId: number) {
	subMenu.value = null
	pollsStore.addPollToPollGroup({
		pollId,
		pollGroupId,
	})
}

async function addPollToNewPollGroup(pollId: number) {
	if (!newGroupTitle.value.trim()) {
		return
	}

	try {
		await pollsStore.addPollToPollGroup({
			pollId,
			groupTitle: newGroupTitle.value.trim(),
		})
		newGroupTitle.value = ''
		subMenu.value = null
	} catch (error) {
		showError(t('polls', 'Error creating new poll group.'))
	}
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
		<template v-if="subMenu">
			<NcActionButton
				:aria-label="t('polls', 'Back')"
				:name="t('polls', 'Back')"
				@click="subMenu = null">
				<template #icon>
					<IconArrowLeft :size="16" />
				</template>
			</NcActionButton>
		</template>

		<template v-else>
			<NcActionButton
				v-show="
					poll.permissions.view && sessionStore.appPermissions.pollCreation
				"
				:name="t('polls', 'Clone poll')"
				:aria-label="t('polls', 'Clone poll')"
				close-after-click
				@click="clonePoll()">
				<template #icon>
					<ClonePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-show="
					(adminAccess || poll.permissions.edit) && !poll.status.isArchived
				"
				:name="t('polls', 'Archive poll')"
				:aria-label="t('polls', 'Archive poll')"
				close-after-click
				@click="toggleArchive()">
				<template #icon>
					<ArchivePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-show="
					(adminAccess || poll.permissions.edit) && poll.status.isArchived
				"
				:name="t('polls', 'Restore poll')"
				:aria-label="t('polls', 'Restore poll')"
				close-after-click
				@click="toggleArchive()">
				<template #icon>
					<RestorePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-show="adminAccess"
				:name="t('polls', 'Take over')"
				:aria-label="t('polls', 'Take over')"
				close-after-click
				@click="showTakeOverDialog = true">
				<template #icon>
					<PlusIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-show="adminAccess || poll.permissions.edit"
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
				v-show="adminAccess || poll.permissions.edit"
				class="danger"
				:name="t('polls', 'Transfer poll ownership')"
				:aria-label="t('polls', 'Transfer poll ownership')"
				close-after-click
				@click="showTransferDialog = true">
				<template #icon>
					<TransferPollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-show="poll.permissions.edit"
				is-menu
				name="Add to group"
				@click="toggleSubMenu('addToGroup')">
				<template #icon>
					<PlusIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-show="poll.permissions.edit && poll.pollGroups.length > 0"
				is-menu
				name="Remove from group"
				@click="toggleSubMenu('removeFromGroup')">
				<template #icon>
					<MinusIcon />
				</template>
			</NcActionButton>
		</template>

		<template v-if="subMenu === 'addToGroup'">
			<NcActionButton
				v-for="pollGroup in pollsStore.addablePollGroups(poll.id)"
				:key="pollGroup.id"
				:name="pollGroup.title"
				@click="addPollToPollGroup(poll.id, pollGroup.id)" />
			<NcActionInput
				v-if="sessionStore.appPermissions.pollCreation"
				v-model="newGroupTitle"
				:name="t('polls', 'Create new group')"
				:aria-label="t('polls', 'Create new group')"
				:placeholder="t('polls', 'New group name')"
				@submit="addPollToNewPollGroup(poll.id)" />
		</template>

		<template v-if="subMenu === 'removeFromGroup'">
			<NcActionButton
				v-for="pollGroup in poll.pollGroups"
				:key="pollGroup"
				:name="pollsStore.getPollGroupName(pollGroup)"
				@click="removePollFromGroup(poll.id, pollGroup)" />
		</template>
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
