<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import VoteColumn from './VoteColumn.vue'
import UserItem from '../User/UserItem.vue'
import { useComboStore } from '../../stores/combo.ts'

import type { ViewMode } from '../../stores/preferences.types'

const { viewMode = 'table-view' } = defineProps<{ viewMode?: ViewMode }>()

const comboStore = useComboStore()
</script>

<template>
	<div :class="['combo-table', viewMode]">
		<div class="user-column">
			<div class="spacer" />
			<div
				v-for="poll in comboStore.polls"
				:key="poll.id"
				:title="poll.configuration.title"
				class="poll-group">
				<div
					v-for="participant in comboStore.participantsInPoll(poll.id)"
					:key="`${participant.user.id}_${participant.pollId}`"
					class="participant">
					<UserItem v-bind="participant" condensed />
				</div>
			</div>
		</div>

		<TransitionGroup name="list" tag="div" class="vote-grid">
			<VoteColumn
				v-for="option in comboStore.uniqueOptions"
				:key="option.id"
				:option="option" />
		</TransitionGroup>
	</div>
</template>

<style lang="scss">
.combo-title {
	margin-bottom: 16px;
}

.combo-table {
	display: flex;
	flex: 1;

	.spacer {
		flex: 1;
	}

	.poll-group {
		display: flex;
		flex-direction: column;
	}

	.participant,
	.vote-item {
		flex: 0 0 auto;
		height: 4.5em;
		line-height: 1.5em;
		padding: 4px;
		border-top: solid 1px var(--color-border-dark);
	}

	.user-column {
		display: flex;
		flex-direction: column;
		overflow-x: scroll;
		margin-bottom: 4px;
		.participant {
			display: flex;
			max-width: 245px;
		}
	}

	.vote-grid {
		display: flex;
		flex: 1;
		overflow-x: scroll;
	}

	.user-column::after,
	.vote-column::after {
		content: '';
		height: 8px;
	}
}
</style>
