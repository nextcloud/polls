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
	<div class="vote-table" :class="tableMode ? 'desktop' : 'mobile'">
		<div class="vote-table__users fixed">
			<UserItem v-for="(participant) in participants"
				:key="participant.userId"
				v-bind="participant"
				:class="{currentuser: (participant.userId === acl.userId) }">
				<Actions v-if="acl.allowEdit" class="action">
					<ActionButton icon="icon-delete" @click="confirmDelete(participant.userId)">
						{{ t('polls', 'Delete votes') }}
					</ActionButton>
				</Actions>
			</UserItem>
		</div>

		<div class="vote-table__header">
			<VoteTableHeaderItem v-for="(option) in rankedOptions"
				:key="option.id"
				:option="option"
				:poll-type="poll.type"
				:table-mode="tableMode" />
		</div>

		<div class="vote-table__votes">
			<div v-for="(participant) in participants"
				:key="participant.userId"
				:class=" {currentuser: (participant.userId === acl.userId) }"
				class="vote-table__vote-row">
				<VoteTableVoteItem v-for="(option) in rankedOptions"
					:key="option.id"
					:user-id="participant.userId"
					:option="option"
					:is-active="acl.userId === participant.userId && acl.allowVote" />
			</div>
		</div>

		<div v-if="expired" class="vote-table__footer">
			<div v-for="(option) in rankedOptions" :key="option.id" :class="{ 'confirmed' : option.confirmed }">
				<Actions v-if="acl.allowEdit"
					class="action">
					<ActionButton v-if="expired" :icon="option.confirmed ? 'icon-polls-confirmed' : 'icon-polls-unconfirmed'"
						@click="confirmOption(option)">
						{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
					</ActionButton>
				</Actions>
			</div>
		</div>

		<div class="vote-table__footer-blind fixed" />

		<div class="vote-table__header-blind fixed" />

		<Modal v-if="modal">
			<div class="modal__content">
				<h2>{{ t('polls', 'Do you want to remove {username} from poll?', { username: userToRemove }) }}</h2>
				<div class="modal__buttons">
					<ButtonDiv :title="t('polls', 'No')"
						@click="modal = false" />
					<ButtonDiv :primary="true" :title="t('polls', 'Yes')"
						@click="removeUser()" />
				</div>
			</div>
		</Modal>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { Actions, ActionButton, Modal } from '@nextcloud/vue'
import orderBy from 'lodash/orderBy'
import VoteTableVoteItem from './VoteTableVoteItem'
import VoteTableHeaderItem from './VoteTableHeaderItem'
import { confirmOption } from '../../mixins/optionMixins'

export default {
	name: 'VoteTable',
	components: {
		Actions,
		ActionButton,
		Modal,
		VoteTableHeaderItem,
		VoteTableVoteItem,
	},

	mixins: [confirmOption],

	props: {
		tableMode: {
			type: Boolean,
			default: false,
		},
		ranked: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			modal: false,
			userToRemove: '',
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			sortedOptions: 'poll/options/sorted',
			participants: 'poll/participants',
			expired: 'poll/expired',
		}),

		rankedOptions() {
			return orderBy(this.sortedOptions, this.ranked ? 'rank' : 'order', 'asc')
		},
	},

	methods: {
		removeUser() {
			this.$store.dispatch('poll/votes/delete', {
				userId: this.userToRemove,
			})
			this.modal = false
			this.userToRemove = ''
		},

		confirmDelete(userId) {
			this.userToRemove = userId
			this.modal = true
		},
	},
}
</script>

<style lang="scss">

// use grid
.vote-table {
	display: grid;
	overflow: scroll;

	// define default flex items
	.vote-table__users,
	.vote-table__header,
	.vote-table__votes,
	.vote-table__footer,
	.vote-table__vote-row,
	.vote-table-header-item,
	.vote-table-vote-item {
		display: flex;
	}

	//set default style for confirmed options
	.vote-table__header,
	.vote-table__vote-row,
	.vote-table__footer {
		> div {
			flex: 1;
			order: 1;
			&.confirmed {
				order: 0;
				border-radius: 10px;
				border: 1px solid var(--color-polls-foreground-yes) !important;
				border-top: 1px solid var(--color-polls-foreground-yes) !important;
				border-bottom: 1px solid var(--color-polls-foreground-yes) !important;
				background-color: var(--color-polls-background-yes) !important;
				padding: 8px 2px;
			}
		}
	}
}

// justify styles for mobile view
.vote-table.mobile {
	grid-template-columns: auto 1fr;
	grid-template-rows: auto;
	grid-template-areas: 'vote header';
	justify-items: stretch;

	.vote-table__header {
		grid-area: header;
		flex-direction: column;

		> div.confirmed {
			border-left: none !important;
			border-bottom-left-radius: 0;
			border-top-left-radius: 0;
		}
	}

	.vote-table__votes {
		grid-area: vote;
		.vote-table__vote-row {
			flex-direction: column;
		}
	}

	.vote-table__header-blind,
	.vote-table__users,
	.vote-table__vote-row:not(.currentuser),
	.vote-table__footer-blind,
	.vote-table__footer {
		display: none;
	}

	.vote-table__header,
	.vote-table__vote-row {
		> div {
			padding-left: 12px;
			padding-right: 12px;
			border-bottom: 1px solid var(--color-border-dark);
			min-height: 3em;
			height: 3em;
			&.confirmed {
				margin-top: 8px;
				margin-bottom: 8px;
				font-weight: bold;
			}
		}
	}

	.vote-table__vote-row {
		> div.confirmed {
			border-right: none !important;
			border-top-right-radius: 0;
			border-bottom-right-radius: 0;
		}
	}

}

.vote-table.desktop {
	grid-template-columns: auto repeat(var(--polls-vote-columns), 1fr);
	grid-template-rows: auto repeat(var(--polls-vote-rows), 1fr) auto;
	grid-template-areas:
		'blind1 options'
		'users vote'
		'blind2 footer';
	justify-items: stretch;
	padding-bottom: 14px; // leave space for the scrollbar!

	.vote-table__header {
		grid-area: options;
		flex-direction: row;

		> div {
			flex-direction: column;
			flex: 1;
			align-items: center;

			&.confirmed {
				border-bottom: none !important;
				border-bottom-left-radius: 0;
				border-bottom-right-radius: 0;
			}
		}
	}

	.vote-table__header-blind {
		grid-area: blind1;
	}

	.vote-table__footer-blind {
		grid-area: blind2;
	}

	.vote-table__votes {
		grid-area: vote;
		flex-direction: column;
	}

	.vote-table__users {
		grid-area: users;
		flex-direction: column;
		> .user-item {
			order: 1;
			&.currentuser {
				order: 0;
			}
		}
	}

	.vote-table__footer {
		grid-area: footer;
		flex-direction: row;

		> div {
			display: flex;
			align-items: center;
			justify-content: center;

			&.confirmed {
				border-top: none !important;
				border-top-left-radius: 0;
				border-top-right-radius: 0;
			}
		}
	}

	.vote-table__header,
	.vote-table__vote-row,
	.vote-table__footer {
		> div {
			max-width: 230px;

			&.confirmed {
				margin-left: 8px;
				margin-right: 8px;
				font-weight: bold;
			}
		}
	}

	// limit width of columns
	.vote-table__vote-row {
		flex-direction: row;
		order: 1;
		flex: 1;

		&.currentuser {
			order: 0;
		}

		> div.confirmed {
			border-top: none !important;
			border-bottom: none !important;
			border-radius: 0;
		}
	}

	.vote-table-vote-item {
		width: 84px;
	}

	// fixed column
	.fixed {
		background-color: var(--color-main-background);
		position: sticky;
		left: 0;
	}

	// Bottom border for table rows
	.vote-table__users .user-item,
	.vote-table__header-blind,
	.vote-table__header > div,
	.vote-table__vote-row > div {
		border-bottom: 1px solid var(--color-border-dark);
	}

	// divergent styles for confirmed optins in table layout

	.option-item {
		.option-item__option--text {
			hyphens: auto;
			text-align: center;
			align-items: center;
			// hack for the hyphens, because hyphenating works different
			// in different browsers and with different languages.
			min-width: 160px;
		}
	}

	// some little hacks
	.user-item {
		max-width: 280px;
	}

	.user-item__name {
		width: unset;
	}
}

</style>
