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

<template>
	<div class="side-bar-tab-polls">
		<div v-for="(poll) in polls"
			:key="poll.id"
			:class="['poll-item', { listed: listed(poll.id) }]"
			@click="toggle(poll.id)">
			<UserItem :user="poll.owner" condensed />
			<div class="poll-title-box">
				{{ poll.configuration.title }}
			</div>
		</div>
	</div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'

export default {
	name: 'SideBarTabDatePolls',

	computed: {
		...mapGetters({
			polls: 'polls/datePolls',
			listed: 'combo/pollIsListed',
		}),
	},
	methods: {
		...mapActions({
			toggle: 'combo/togglePollItem',
		}),

	},
}
</script>

<style lang="scss">
.poll-item {
	display: flex;
	align-items: center;
	&.listed {
		background-color: var(--color-polls-background-yes);
		margin: 8px 0;
		border-bottom: 1px solid var(--color-border);
		border-radius: var(--border-radius-large);
		box-shadow: 2px 2px 6px var(--color-box-shadow);
	}
	.poll-title-box {
		transition: background-color 1s ease-out;
	}
}
</style>
