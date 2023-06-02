<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template lang="html">
	<NcAppNavigationItem :name="poll.title"
		:to="{name: 'vote', params: {id: poll.id}}"
		:class="{ closed: closed }">
		<template #icon>
			<TextPollIcon v-if="poll.type === 'textPoll'" />
			<DatePollIcon v-else />
		</template>
		<template #actions>
			<NcActionButton v-if="isPollCreationAllowed"
				@click="$emit('clone-poll')">
				<template #icon>
					<ClonePollIcon />
				</template>
				{{ t('polls', 'Clone poll') }}
			</NcActionButton>

			<NcActionButton v-if="poll.allowEdit && !poll.deleted"
				@click="$emit('toggle-archive')">
				<template #icon>
					<ArchivePollIcon />
				</template>
				{{ t('polls', 'Archive poll') }}
			</NcActionButton>

			<NcActionButton v-if="poll.allowEdit && poll.deleted"
				@click="$emit('toggle-archive')">
				<template #icon>
					<RestorePollIcon />
				</template>
				{{ t('polls', 'Restore poll') }}
			</NcActionButton>

			<NcActionButton v-if="poll.allowEdit && poll.deleted"
				class="danger"
				@click="$emit('delete-poll')">
				<template #icon>
					<DeletePollIcon />
				</template>
				{{ t('polls', 'Delete poll') }}
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
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
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
		...mapState({
			isPollCreationAllowed: (state) => state.polls.isPollCreationAllowed,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
		}),
	},
}
</script>
