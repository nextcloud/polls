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
	<div class="vote-table" :class="{ 'owner-access': acl.allowEdit, 'listMode': !tableMode }">
		<div class="vote-table__header">
			<div class="user-div" />

			<VoteTableHeader v-for="(option) in sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="poll.type"
				:table-mode="tableMode" />
		</div>

		<div v-for="(participant) in participants"
			:key="participant.userId"
			:class=" {currentuser: (participant.userId === acl.userId) }"
			class="vote-table__vote-row">
			<UserDiv :key="participant.userId"
				v-bind="participant"
				class="vote-table__user-column"
				:class="{currentuser: (participant.userId === acl.userId) }">
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

	props: {
		tableMode: {
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

<style lang="scss">

.vote-table {
	display: flex;
	flex: 0 auto;
	flex-direction: column;
	justify-content: flex-start;
	overflow-x: scroll;
	padding-bottom: 12px;
	background-color: var(--color-main-background);
}

.vote-table__vote-row, .vote-table__header {
	display: flex;
	flex: 1;
	border-bottom: 1px solid var(--color-border-dark);
	background-color: var(--color-main-background);
	justify-content: space-between;
	min-width: max-content;
}

.vote-table__header {
	order: 1;
}

.vote-table__vote-row {
	order: 3;
	&.currentuser {
		order: 2;
	}
}

.user-div {
	position: sticky;
	left: 0;
	background-color: var(--color-main-background);
	width: 230px;
	flex: 0 auto;
	.owner-access {
		width: 280px;
	}
}
.counter {
	display: flex;
}

.counter2 {
	display: none;
}

.vote-item, .vote-table-header {
	width: 84px;
	min-width: 84px;
	flex: 1;
	margin: 2px;
}

.vote-table.listMode {
	flex: 0 auto;
	flex-direction: row;
	min-width: 300px;
	.vote-item, .vote-table-header {
		margin: 0;
		border-top: 1px solid var(--color-border-dark);
	}

	.counter {
		display: none;
	}

	.counter2 {
		display: flex;
	}

	.vote-table__vote-row:not(.currentuser), .user-div {
		display: none;
	}

	.vote-table__vote-row.currentuser {
		display: flex;
		flex-direction: column;
		order: 0;
		flex: 0;
	}

	.vote-table-header {
		flex-direction: row;
		width: unset;
		min-width: unset;
	}

	.option-item {
		flex: 2;
	}

	.vote-table__header, {
		flex-direction: column;
	}
}

</style>
