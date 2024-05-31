<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
import UserItem from '../User/UserItem.vue'

export default {
	name: 'SideBarTabDatePolls',

	components: {
		UserItem,
	},
	
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
