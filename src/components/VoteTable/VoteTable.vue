<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { showSuccess } from '@nextcloud/dialogs'
	import { t } from '@nextcloud/l10n'

	import { ActionDelete } from '../Actions/index.js'

	import VoteColumn from './VoteColumn.vue'
	import VoteMenu from './VoteMenu.vue'
	import UserItem from '../User/UserItem.vue'
	import { usePollStore } from '../../stores/poll.ts'
	import { useSessionStore } from '../../stores/session.ts'
	import { useOptionsStore } from '../../stores/options.ts'
	import { useVotesStore } from '../../stores/votes.ts'

	const pollStore = usePollStore()
	const sessionStore = useSessionStore()
	const optionsStore = useOptionsStore()
	const votesStore = useVotesStore()

	async function removeUser(userId: string) {
		await votesStore.deleteUser({ userId })
		showSuccess(t('polls', 'Participant {userId} has been removed', { userId }))
	}

</script>

<template>
	<div class="vote-table" :class="[pollStore.viewMode, { closed: pollStore.isClosed }]">
		<div class="vote-table__users sticky-left">
			<div class="column-header">
				<VoteMenu />
			</div>

			<div v-for="(participant) in pollStore.safeParticipants"
				:key="participant.id"
				:class="['participant', {'current-user': (participant.id === sessionStore.currentUser.id) }]">
				<UserItem :user="participant" condensed />

				<ActionDelete v-if="pollStore.permissions.edit"
					class="user-actions"
					:name="t('polls', 'Delete votes')"
					@delete="removeUser(participant.id)" />
			</div>

			<div v-if="optionsStore.proposalsExist" class="owner" />

			<div v-if="pollStore.permissions.edit && pollStore.isClosed" class="confirm" />
		</div>

		<TransitionGroup tag="div"
			name="list"
			class="vote-table__votes">
			<VoteColumn v-for="(item) in optionsStore.orderedOptions"
				:key="item.id"
				:option="item"
				:view-mode="pollStore.viewMode" />
		</TransitionGroup>
	</div>
</template>

<style lang="scss">

.vote-table {
	display: flex;
	flex: 1;
	overflow-x: scroll;

	.participant, .vote-item {
		flex: 0 0 auto;
		height: 4.5em;
		order: 10;
		line-height: 1.5em;
		padding: 6px;
		border-radius: 12px;
		&.current-user {
			order:5;
		}
	}

	.participant {
		display: flex;
		align-self: stretch;
		justify-content: center;

		.user-actions {
			visibility: hidden;
		}

		&:hover {
			background: var(--color-background-hover);
			.user-actions {
				visibility: visible;
			}
		}
	}

	.vote-table__users {
		display: flex;
		flex-direction: column;
		padding-bottom: 4px;
		align-items: flex-start;

		&.sticky-left {
			position: sticky;
			left: 0;
			z-index: 2;
			background-color: var(--color-main-background);
		}
	}

	.vote-table__votes {
		display: flex;
		flex: 1;
		overflow-x: scroll;
	}

	.vote-column {
		order: 2;
		display: flex;
		flex: 1 0 11em;
		flex-direction: column;
		align-items: stretch;
		max-width: 19em;
		margin-bottom: 4px;

		&>div {
			display: flex;
			justify-content: center;
			align-items: center;
		}
		.option-item {
			flex: 1;
			order: 1;
		}

	}

	&.closed .vote-column {
		&.confirmed {
			order: 1;
			border-radius: 10px;
			border: 1px solid var(--color-polls-foreground-yes);
			background-color: var(--color-polls-background-yes);
			margin: 4px 4px;
		}
	}

	.vote-item {
		background-clip: content-box;
	}

	.confirmation {
		order:3;
		padding: 4px;
	}

	.counter {
		order:3;
	}

	.calendar-peek {
		order:2;
	}

	.confirm {
		height: 45px;
		order: 20;
	}

	.owner {
		display: flex;
		flex: 0 auto;
		height: 1.6em;
		line-height: 1.6em;
		min-width: 24px;
		order: 19;
	}

	.spacer {
		flex: 1;
		order: 0;
	}

	&.table-view {
		.column-header {
			flex: 1;
			flex-direction: column;
			&.sticky-top {
				position: sticky;
				top: 78px;
				background-color: var(--color-main-background);
				padding-bottom: 4px;
				z-index: 1;
			}
		}

		.vote-table__users::after, .vote-column::after {
			content: '';
			height: 8px;
			order: 99;
		}

		.participant {
			max-width: 245px;
		}

		.option-item .option-item__option--text {
			text-align: center;
			/* Notice: https://caniuse.com/css-text-wrap-balance */
			text-wrap: balance;
			hyphens: auto;
			padding: 0 0.6em;
		}

		.participant, .vote-item {
			&.current-user {
				margin-bottom: 30px;
			}
		}
	}

	&.list-view {
		flex-direction: column;

		.vote-column {
			flex-direction: row-reverse;
			flex: 1 5.5em;
			align-items: center;
			max-width: initial;
			position: relative;
			border-top: solid 1px var(--color-border);
			border-left: none;
			padding: 0;

			.column-header {
				flex-direction: row-reverse;
				order: 1;
				flex: 1;
				justify-content: space-between;
			}

			&.locked {
				background-color: var(--color-polls-background-no);
			}
		}

		.participant {
			border-top: none;
		}

		.participant:not(.current-user), .vote-item:not(.current-user) {
			display: none;
		}

		&.closed {
			.vote-column {
				padding: 2px 8px;
				&.confirmed {
					margin: 4px 0;
				}
			}
		}

		.confirm {
			display: none;
		}

		.owner {
			order: 0;
			flex: 0;
			// justify-content: flex-end;
		}

		.counter {
			order: 0;
			flex: 0;
			flex-direction: column;
			padding-left: 12px;
		}

		.vote-table__users {
			margin: 0;
			flex-direction: revert;

			.confirm, .owner {
				display: none;
			}
		}

		.vote-table__votes {
			align-items: stretch;
			flex-direction: column;
		}

		.option-item {
			flex-direction: row;
			padding: 8px 4px;
		}

		.vote-item.current-user {
			border: none;
		}

		@media only screen and (max-width: 370px) {
			.owner {
				display: none;
			}
		}

		@media only screen and (max-width: 340px) {
			.calendar-peek {
				display: none;
			}
		}

		.calendar-peek {
			order: 0;
			padding-left:4px;
		}

		.calendar-peek__conflict.icon {
			width: 24px;
			height: 24px;
		}

		.calendar-peek__caption {
			display: none;
		}

		.option-item__option--datebox {
			min-width: 120px;
		}
	}
}

</style>
