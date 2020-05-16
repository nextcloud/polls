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
	<div class="vote-table">
		<div class="vote-table__header">
			<VoteTableHeader v-for="(option) in rankedOptions"
				:key="option.id"
				:option="option"
				:poll-type="poll.type"
				:table-mode="tableMode" />
		</div>
		<div class="vote-table__users">
			<UserDiv v-for="(participant) in participants"
				:key="participant.userId"
				v-bind="participant"
				class="vote-table__user-column"
				:class="{currentuser: (participant.userId === acl.userId) }">
				<Actions v-if="acl.allowEdit" class="action">
					<ActionButton icon="icon-delete" @click="confirmDelete(participant.userId)">
						{{ t('polls', 'Delete votes') }}
					</ActionButton>
				</Actions>
			</UserDiv>
		</div>
		<div class="vote-table__votes">
			<div v-for="(participant) in participants"
				:key="participant.userId"
				:class=" {currentuser: (participant.userId === acl.userId) }"
				class="vote-table__vote-row">
				<VoteItem v-for="(option) in rankedOptions"
					:key="option.id"
					:user-id="participant.userId"
					:option="option"
					:is-active="acl.userId === participant.userId && acl.allowVote"
					@voteClick="setVote(option, participant.userId)" />
			</div>
		</div>
		<div class="vote-table__footer">
			<VoteTableFooter v-for="(option) in rankedOptions"
				:key="option.id"
				:option="option"
				:poll-type="poll.type"
				:table-mode="tableMode" />
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
import { mapState, mapGetters } from 'vuex'
import { Actions, ActionButton, Modal } from '@nextcloud/vue'
import orderBy from 'lodash/orderBy'
import VoteItem from './VoteItem'
import VoteTableHeader from './VoteTableHeader'
import VoteTableFooter from './VoteTableFooter'

export default {
	name: 'VoteTable',
	components: {
		Actions,
		ActionButton,
		Modal,
		VoteTableHeader,
		VoteTableFooter,
		VoteItem,
	},

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
			acl: state => state.acl,
		}),

		...mapGetters([
			'sortedOptions',
			'participants',
		]),

		rankedOptions() {
			return orderBy(this.sortedOptions, this.ranked ? 'rank' : 'order', 'asc')
		},
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

.vote-table {
	display: grid;
	overflow: scroll;
	grid-template-columns: 250px repeat(var(--polls-vote-columns), 1fr);
	grid-template-rows: auto repeat(var(--polls-vote-rows), 1fr) 50px;
	justify-items: stretch;
	grid-template-areas:
		". headers"
		"users vote"
		". footer" ;
}
.vote-table__header {
	grid-area: headers;
	display: flex;

	border-bottom: 1px solid var(--color-border-dark);
	background-color: var(--color-main-background);
	justify-content: space-around;
}

.vote-table__users {
	grid-area: users;
	display:flex;
	flex-direction: column;
	position: sticky;
	left: 0;
	background-color: var(--color-main-background);
}

.vote-table__votes {
	grid-area: vote;
	display: flex;
	flex-direction: column;
}

.vote-table__footer {
	grid-area: footer;
	display: flex;
}

.vote-table__vote-row,  {
	display: flex;
	flex: 1;
	order: 3;
	&.currentuser {
		order: 2;
	}
}

.vote-item {
	width: 84px;
	min-width: 84px;
	flex: 1;
	margin: 2px;
	&.confirmed {
		border-left: 1px solid var(--color-polls-foreground-yes);
		border-right: 1px solid var(--color-polls-foreground-yes);
		min-width: 100px;
		background-color: var(--color-polls-background-yes);
		margin: 0 8px;
	}
}

.vote-table.listMode {
	flex: 0 auto;
	flex-direction: row;
	min-width: 300px;
	.vote-item, .vote-table-header {
		margin: 0;
		border-top: 1px solid var(--color-border-dark);
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

	.vote-table__footer-row {
		display: none;
	}

	.vote-table-header {
		flex-direction: row;
		width: unset;
		min-width: unset;
	}

	.option-item {
		flex: 2;
	}

	.vote-table__header-row, {
		flex-direction: column;
	}
}

</style>
