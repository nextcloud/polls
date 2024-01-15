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
	<div class="vote-table" :class="[viewMode, { closed: closed }]">
		<div class="vote-table__users">
			<VoteMenu />

			<div class="spacer" />

			<div v-for="(participant) in participants"
				:key="participant.userId"
				:class="['participant', {currentuser: (participant.userId === currentUser.userId) }]">
				<UserItem :user="participant" condensed />

				<ActionDelete v-if="permissions.edit"
					class="user-actions"
					:name="t('polls', 'Delete votes')"
					@delete="removeUser(participant.userId)" />
			</div>

			<div v-if="proposalsExist" class="owner" />

			<div v-if="permissions.edit && closed" class="confirm" />
		</div>

		<transition-group name="list" tag="div" class="vote-table__votes">
			<VoteColumn v-for="(item) in options"
				:key="item.id"
				:option="item"
				:view-mode="viewMode" />
		</transition-group>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { showSuccess } from '@nextcloud/dialogs'
import { ActionDelete } from '../Actions/index.js'
import VoteColumn from './VoteColumn.vue'
import VoteMenu from './VoteMenu.vue'
import { confirmOption } from '../../mixins/optionMixins.js'

export default {
	name: 'VoteTable',
	components: {
		ActionDelete,
		VoteColumn,
		VoteMenu,
	},

	mixins: [confirmOption],

	props: {
		viewMode: {
			type: String,
			default: 'table-view',
			validator(value) {
				return ['table-view', 'list-view'].includes(value)
			},
		},
	},

	computed: {
		...mapState({
			permissions: (state) => state.poll.acl.permissions,
			currentUser: (state) => state.poll.acl.currentUser,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			participants: 'poll/safeParticipants',
			options: 'options/rankedOptions',
			proposalsExist: 'options/proposalsExist',
		}),

	},

	methods: {
		async removeUser(userId) {
			await this.$store.dispatch('votes/deleteUser', { userId })
			showSuccess(t('polls', 'User {userId} removed', { userId }))
		},
	},
}
</script>

<style lang="scss">
.vote-table {
	display: flex;
	flex: 1;

	.participant, .vote-item {
		flex: 0 0 auto;
		height: 4.5em;
		order: 10;
		line-height: 1.5em;
		padding: 6px;
		border-radius: 12px;
		&.currentuser {
			order:5;
		}
	}

	.participant {
		display: flex;
		align-self: stretch;
		justify-content: center;

		.user-actions {
			// display: none;
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
		.vote-table__users::after, .vote-column::after {
			content: '';
			height: 8px;
			order: 99;
		}
		.vote-table__users {
			overflow-x: scroll;
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
			&.currentuser {
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

			&.locked {
				background-color: var(--color-polls-background-no);
			}
		}

		.participant {
			border-top: none;
		}

		.participant:not(.currentuser), .vote-item:not(.currentuser) {
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
			flex: 1;
			justify-content: flex-end;
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
			&.date-box {
				flex: 0;
				align-items: baseline;
			}
		}

		.vote-item.currentuser {
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
