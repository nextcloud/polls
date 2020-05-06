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
	<div class="vote-table" :class="{ 'owner-access': acl.allowEdit }">
		<div class="vote-table__header">
			<div class="vote-table__user-column" />

			<VoteTableHeader v-for="(option) in sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="poll.type" />
		</div>

		<div v-for="(participant) in participants" :key="participant.userId" :class="{currentuser: (participant.userId === acl.userId) }">
			<UserDiv :key="participant.userId"
				class="vote-table__user-column"
				:disable-menu="true"
				:class="{currentuser: (participant.userId === acl.userId) }"
				:user-id="participant.userId"
				:display-name="participant.displayName">
				<Actions v-if="acl.allowEdit" class="action">
					<ActionButton icon="icon-delete"
						@click="confirmDelete(participant.userId)">
						{{ t('polls', 'Delete votes') }}
					</ActionButton>
				</Actions>
			</UserDiv>

			<VoteItem v-for="(option) in sortedOptions"
				:key="option.id"
				:user-id="participant.userId"
				:option="option"
				:is-active="acl.userId === participant.userId && acl.allowVote"
				@voteClick="setVote(option, participant.userId)" />
		</div>

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
import VoteItem from './VoteItem'
import VoteTableHeader from './VoteTableHeader'
import { mapState, mapGetters } from 'vuex'
import { Actions, ActionButton, Modal } from '@nextcloud/vue'

export default {
	name: 'VoteTable',
	components: {
		Actions,
		ActionButton,
		Modal,
		VoteTableHeader,
		VoteItem,
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
			acl: state => state.acl,
		}),

		...mapGetters([
			'sortedOptions',
			'participants',
		]),
	},

	methods: {
		removeUser() {
			this.$store.dispatch('deleteVotes', {
				userId: this.userToRemove,
			})
			this.modal = false
			this.userToRemove = ''
		},

		confirmDelete(userId) {
			this.userToRemove = userId
			this.modal = true
		},

		setVote(option, userId) {
			this.$store
				.dispatch('setVoteAsync', {
					option: option,
					userId: userId,
					setTo: this.$store.getters.getNextAnswer({
						option: option,
						userId: userId,
					}),
				})
		},
	},
}
</script>

<style lang="scss" scoped>
	.user-row.vote-table__user-column,
	.vote-table__header > .vote-table__user-column {
		position: sticky;
		left: 0;
		background-color: var(--color-main-background);
		width: 230px;
		flex: 0 0 auto;
		.owner-access {
			width: 280px;
		}
	}

	.owner-access .user-row.vote-table__user-column,
	.owner-access .vote-table__header > .vote-table__user-column {
		width: 280px;
	}

	.user {
		height: 44px;
		padding: 0 17px;
	}

	.vote-table {
		display: flex;
		flex: 0;
		flex-direction: column;
		justify-content: flex-start;
		overflow: scroll;
		padding: 10px 0;

		& > div {
			display: flex;
			flex: 1;
			border-bottom: 1px solid var(--color-border-dark);
			order: 3;
			justify-content: space-between;
			min-width: max-content;

			& > div {
				width: 84px;
				min-width: 84px;
				flex: 1;
				margin: 2px;
			}

			& > .vote-header {
				flex: 1;
			}

			&.vote-table__header {
				order: 1;
			}

			&.currentuser {
				order: 2;
			}
		}

		.vote-row {
			display: flex;
			justify-content: space-around;
			flex: 1;
			align-items: center;
		}
		.vote-item {
			flex: 1;
		}
	}

	@media (max-width: (480px)) {
		.vote-table {
			flex: 1 0;
			flex-direction: row;
			min-width: 300px;

			&> div {
				display: none;
				&> div {
					width: unset;
					margin: 0;

				}
			}

			&> .currentuser {
				display: flex;
				flex-direction: column;
				&> .user-row {
					display: none;
				}
			}

			&> .vote-table__header, {
				height: initial;
				padding-left: initial;
				display: flex;
				flex-direction: column;
				flex: 3 1;
				justify-content: space-around;
				align-items: stretch;
				&> .vote-header {
					display: flex;
					flex-direction: row;
					&> .counter {
						align-items: baseline;
					}
				}
			}
		}
	}
</style>
