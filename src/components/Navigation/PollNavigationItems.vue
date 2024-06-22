<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigationItem :name="poll.configuration.title" :to="{name: 'vote', params: {id: poll.id}}" :class="{ closed: poll.status.expired }">
		<template #icon>
			<TextPollIcon v-if="poll.type === 'textPoll'" />
			<DatePollIcon v-else />
		</template>
		<template #actions>
			<NcActionButton v-if="pollsStore.meta.permissions.pollCreationAllowed"
				:name="t('polls', 'Clone poll')"
				:aria-label="t('polls', 'Clone poll')"
				@click="$emit('clone-poll')">
				<template #icon>
					<ClonePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton v-if="poll.permissions.edit && !poll.status.deleted"
				:name="t('polls', 'Archive poll')"
				:aria-label="t('polls', 'Archive poll')"
				@click="$emit('toggle-archive')">
				<template #icon>
					<ArchivePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton v-if="poll.permissions.edit && poll.status.deleted"
				:name="t('polls', 'Restore poll')"
				:aria-label="t('polls', 'Restore poll')"
				@click="$emit('toggle-archive')">
				<template #icon>
					<RestorePollIcon />
				</template>
			</NcActionButton>

			<NcActionButton v-if="poll.permissions.edit && poll.status.deleted"
				class="danger"
				:name="t('polls', 'Delete poll')"
				:aria-label="t('polls', 'Delete poll')"
				@click="$emit('delete-poll')">
				<template #icon>
					<DeletePollIcon />
				</template>
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>

<script>

import { mapStores } from 'pinia'
import { NcActionButton, NcAppNavigationItem } from '@nextcloud/vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import { t } from '@nextcloud/l10n'
import { usePollsStore } from '../../stores/polls.ts'

export default {
	name: 'PollNavigationItems',

	components: {
		NcActionButton,
		NcAppNavigationItem,
		DeletePollIcon,
		ClonePollIcon,
		ArchivePollIcon,
		RestorePollIcon,
		TextPollIcon,
		DatePollIcon,
	},

	props: {
		poll: {
			type: Object,
			default: undefined,
		},
	},

	computed: {
		...mapStores(usePollsStore),
	},

	methods: {
		t,
	},
}
</script>
