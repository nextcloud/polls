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
	<AppNavigationItem :title="poll.title"
		:icon="pollTypeIcon"
		:to="{name: 'vote', params: {id: poll.id}}"
		:class="{ closed: closed }">
		<template #actions>
			<ActionButton v-if="isPollCreationAllowed"
				icon="icon-md-clone-poll"
				@click="$emit('clone-poll')">
				{{ t('polls', 'Clone poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && !poll.deleted"
				icon="icon-md-archive-poll"
				@click="$emit('toggle-archive')">
				{{ t('polls', 'Archive poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted"
				icon="icon-md-restore-poll"
				@click="$emit('toggle-archive')">
				{{ t('polls', 'Restore poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted"
				icon="icon-md-delete-poll"
				class="danger"
				@click="$emit('delete-poll')">
				{{ t('polls', 'Delete poll') }}
			</ActionButton>
		</template>
	</AppNavigationItem>
</template>

<script>

import { mapState, mapGetters } from 'vuex'
import { ActionButton, AppNavigationItem } from '@nextcloud/vue'

export default {
	name: 'PollNavigationItems',

	components: {
		ActionButton,
		AppNavigationItem,
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

		pollTypeIcon() {
			if (this.poll.type === 'textPoll') {
				return 'icon-md-text-poll'
			}

			return 'icon-md-date-poll'
		},
	},
}
</script>
