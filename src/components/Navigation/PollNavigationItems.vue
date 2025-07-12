<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'

import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

import { useSessionStore } from '../../stores/session.ts'
import { Poll } from '../../Types/index.ts'

const emit = defineEmits(['clonePoll', 'toggleArchive', 'deletePoll'])
const { poll } = defineProps<{ poll: Poll }>()

const sessionStore = useSessionStore()
</script>

<template>
	<NcAppNavigationItem
		:name="poll.configuration.title"
		:to="{ name: 'vote', params: { id: poll.id } }"
		:class="{ closed: poll.status.isExpired }">
		<template #icon>
			<TextPollIcon v-if="poll.type === 'textPoll'" />
			<DatePollIcon v-else />
		</template>
		<template #actions>
			<NcActionButton
				v-if="sessionStore.appPermissions.pollCreation"
				:name="t('polls', 'Clone poll')"
				:aria-label="t('polls', 'Clone poll')"
				@click="emit('clonePoll')">
				<template #icon>
					<ClonePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-if="poll.permissions.edit && !poll.status.isArchived"
				:name="t('polls', 'Archive poll')"
				:aria-label="t('polls', 'Archive poll')"
				@click="emit('toggleArchive')">
				<template #icon>
					<ArchivePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-if="poll.permissions.edit && poll.status.isArchived"
				:name="t('polls', 'Restore poll')"
				:aria-label="t('polls', 'Restore poll')"
				@click="emit('toggleArchive')">
				<template #icon>
					<RestorePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-if="poll.permissions.edit && poll.status.isArchived"
				class="danger"
				:name="t('polls', 'Delete poll')"
				:aria-label="t('polls', 'Delete poll')"
				@click="emit('deletePoll')">
				<template #icon>
					<DeletePollIcon />
				</template>
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>
