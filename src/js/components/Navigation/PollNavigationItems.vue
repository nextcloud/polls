<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigationItem :name="poll.configuration.title" :to="{name: 'vote', params: {id: poll.id}}" :class="{ closed: isPollClosed }">
		<template #icon>
			<TextIndPollIcon v-if="poll.type === 'textIndPoll'" />
			<TextRankPollIcon v-else-if="poll.type === 'textRankPoll'" />
			<DatePollIcon v-else />
		</template>
		<template #actions>
			<NcActionButton v-if="pollCreationAllowed"
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

import { mapState, mapGetters } from 'vuex'
import { NcActionButton, NcAppNavigationItem } from '@nextcloud/vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import TextIndPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import TextRankPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

export default {
	name: 'PollNavigationItems',

	components: {
		NcActionButton,
		NcAppNavigationItem,
		DeletePollIcon,
		ClonePollIcon,
		ArchivePollIcon,
		RestorePollIcon,
		TextIndPollIcon,
		TextRankPollIcon,
		DatePollIcon,
	},

	props: {
		poll: {
			type: Object,
			default: undefined,
		},
	},

	computed: {
		...mapState({
			pollCreationAllowed: (state) => state.polls.meta.permissions.pollCreationAllowed,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
		}),
	},
}
</script>
