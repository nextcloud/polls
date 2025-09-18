<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { usePollsStore } from '../../stores/polls'
import { useComboStore } from '../../stores/combo'

import UserItem from '../User/UserItem.vue'

const pollsStore = usePollsStore()
const comboStore = useComboStore()
</script>

<template>
	<div
		v-for="poll in pollsStore.datePolls"
		:key="poll.id"
		:class="['poll-item', { listed: comboStore.pollIsListed(poll.id) }]"
		@click="comboStore.togglePollItem(poll.id)">
		<UserItem :user="poll.owner" condensed />
		<div class="poll-title-box">
			{{ poll.configuration.title }}
		</div>
	</div>
</template>

<style lang="scss">
.poll-item {
	display: flex;
	align-items: center;
	&.listed {
		background-color: var(--color-polls-background-yes);
		margin: 8px 0;
		border-bottom: 1px solid var(--color-border);
		border-radius: var(--border-radius-element);
		box-shadow: 2px 2px 6px var(--color-box-shadow);
	}
	.poll-title-box {
		transition: background-color 1s ease-out;
	}
}
</style>
