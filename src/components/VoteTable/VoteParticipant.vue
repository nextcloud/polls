<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import UserItem from '../User/UserItem.vue'
import { usePollStore } from '../../stores/poll.ts'
import { useSessionStore } from '../../stores/session.ts'
import { useVotesStore } from '../../stores/votes.ts'

import { NcActionButton, NcActions, NcActionText } from '@nextcloud/vue'

import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import VoteMenu from './VoteMenu.vue'
import { User } from '../../Types/index.ts'
import { computed } from 'vue'

const pollStore = usePollStore()
const sessionStore = useSessionStore()
const votesStore = useVotesStore()

const { user } = defineProps<{ user: User }>()

/**
 *
 * @param userId
 */
async function removeUser(userId: string) {
	await votesStore.resetUserVotes({ userId })
	showSuccess(t('polls', 'Participant {userId} has been removed', { userId }))
}

const userItemClass = computed(() => ({
	'current-user': user.id === sessionStore.currentUser.id,
}))
</script>

<template>
	<UserItem
		v-if="pollStore.viewMode === 'table-view'"
		:user="user"
		condensed
		:class="userItemClass">
		<template
			v-if="
				pollStore.permissions.edit || user.id === sessionStore.currentUser.id
			"
			#menu>
			<NcActions
				v-if="
					user.id !== sessionStore.currentUser.id
					&& pollStore.permissions.changeForeignVotes
				"
				class="user-menu"
				placement="right"
				:variant="'tertiary-no-background'"
				force-menu>
				<NcActionText :name="user.displayName" />
				<NcActionButton
					:name="
						t('polls', 'Remove votes of {displayName}', {
							displayName: user.displayName,
						})
					"
					@click="removeUser(user.id)">
					<template #icon>
						<DeleteIcon />
					</template>
				</NcActionButton>
			</NcActions>
			<VoteMenu
				v-if="user.id === sessionStore.currentUser.id"
				class="user-menu"
				placement="right"
				:variant="'tertiary-no-background'"
				force-menu
				no-menu-icon>
			</VoteMenu>
		</template>
	</UserItem>
</template>
