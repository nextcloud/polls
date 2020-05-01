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
	<AppNavigationItem :title="poll.title" :icon="pollIcon" :to="{name: 'vote', params: {id: poll.id}}">
		<template slot="actions">
			<ActionButton icon="icon-add" @click="$emit('clonePoll')">
				{{ t('polls', 'Clone poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && !poll.deleted" icon="icon-delete" @click="$emit('switchDeleted')">
				{{ t('polls', 'Delete poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted" icon="icon-history" @click="$emit('switchDeleted')">
				{{ t('polls', 'Restore poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted" icon="icon-delete" class="danger"
				@click="$emit('deletePermanently')">
				{{ t('polls', 'Delete poll permanently') }}
			</ActionButton>
		</template>
	</AppNavigationItem>
</template>

<script>

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
		pollIcon() {
			if (this.poll.type === 'datePoll') {
				return 'icon-calendar-000'
			} else {
				return 'icon-toggle-filelist'
			}
		},
	},
}
</script>

<style lang="scss">
.icon-calendar-000 {
	background-image: var(--icon-calendar-000);
}
</style>
