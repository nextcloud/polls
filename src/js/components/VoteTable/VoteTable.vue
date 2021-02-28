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
			<div class="spacer" />

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

			<div v-if="acl.allowEdit && closed" class="confirm" />
		</div>

		<div class="vote-table__votes">
			<div v-for="(option) in rankedOptions" :key="option.id" :class="['vote-column', { 'confirmed' : option.confirmed }]">
				<VoteTableHeaderItem :option="option" :view-mode="viewMode" />

				<Confirmation v-if="option.confirmed && closed" :option="option" />

				<Counter v-else :show-maybe="!!poll.allowMaybe"
					:option="option"
					:counter-style="viewMode === 'table-view' ? 'iconStyle' : 'barStyle'"
					:show-no="viewMode === 'list-view'" />
				<CalendarPeek v-if="poll.type === 'datePoll' && getCurrentUser() && settings.calendarPeek" :option="option" />
				<div v-for="(participant) in participants" :key="participant.userId" class="vote-item-wrapper"
					:class="{currentuser: participant.userId === acl.userId}">
					<VoteItem :user-id="participant.userId" :option="option" />
				</div>

				<Actions v-if="acl.allowEdit && closed" class="action confirm">
					<ActionButton v-if="closed" :icon="option.confirmed ? 'icon-polls-confirmed' : 'icon-polls-unconfirmed'"
						@click="confirmOption(option)">
						{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
					</ActionButton>
				</Actions>
				<!-- <div v-if="closed" class="vote-table__footer">
				</div> -->
			</div>
		</div>

		<!--  -->

		<Modal v-if="modal">
			<div class="modal__content">
				<h2>{{ t('polls', 'Do you want to remove {username} from poll?', { username: userToRemove }) }}</h2>
				<div class="modal__buttons">
					<ButtonDiv :title="t('polls', 'No')" @click="modal = false" />
					<ButtonDiv :primary="true" :title="t('polls', 'Yes')" @click="removeUser()" />
				</div>
			</div>
		</Modal>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { showSuccess } from '@nextcloud/dialogs'
import { Actions, ActionButton, Modal } from '@nextcloud/vue'
import orderBy from 'lodash/orderBy'
import CalendarPeek from '../Calendar/CalendarPeek'
import Counter from '../Base/Counter'
import Confirmation from '../Base/Confirmation'
import VoteItem from './VoteItem'
import VoteTableHeaderItem from './VoteTableHeaderItem'
import { confirmOption } from '../../mixins/optionMixins'

export default {
	name: 'VoteTable',
	components: {
		Actions,
		ActionButton,
		CalendarPeek,
		Counter,
		Confirmation,
		Modal,
		VoteTableHeaderItem,
		VoteItem,
	},

	mixins: [confirmOption],

	props: {
		viewMode: {
			type: String,
			default: 'table-view',
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
			acl: state => state.poll.acl,
			poll: state => state.poll,
			settings: state => state.settings.user,
			options: state => state.options.list,
		}),

		...mapGetters({
			closed: 'poll/closed',
			participants: 'poll/participants',
		}),

		rankedOptions() {
			return orderBy(this.options, this.ranked ? 'rank' : 'order', 'asc')
		},
	},

	methods: {
		async removeUser() {
			this.modal = false
			await this.$store.dispatch('votes/deleteUser', { userId: this.userToRemove })
			showSuccess(t('polls', 'User {userId} removed', { userId: this.userToRemove }))
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

.vote-table {
	display: flex;
	flex: 1;
	.user-item, .vote-item-wrapper {
		flex: 0;
		height: 53px;
		min-height: 53px;
		border-top: solid 1px var(--color-border-dark);
		order: 10;
		&.currentuser {
			order:5;
		}
	}

	.vote-table-header-item {
		flex: 1;
		flex-direction: column;
		align-items: stretch;
		padding: 0 8px;
		order:1;
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

	.spacer {
		flex: 1;
		order: 1;
	}

	.vote-table__users {
		display: flex;
		flex-direction: column;
		overflow-x: scroll;
		min-width: 90px;
		.user-item__name {
			min-width: initial;
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
		flex: 1 0 auto;
		flex-direction: column;
		align-items: stretch;
		min-width: 85px;
		max-width: 280px;
		.vote-item {
			flex-direction: column;
		}
	}

	&.closed .vote-table__users {
		padding: 8px 2px;
	}

	&.closed .vote-column {
		padding: 8px 2px;
		&.confirmed {
			order: 1;
			border-radius: 10px;
			border: 1px solid var(--color-polls-foreground-yes);
			background-color: var(--color-polls-background-yes);
			margin: 0 4px;
		}
	}

	.vote-item-wrapper {
		display: flex;
		padding: 4px 1px;
	}

	.vote-table__footer {
		align-items: center;
	}
}

.vote-table.table-view {
	.option-item .option-item__option--text {
		text-align: center;
	}
}

.vote-table.list-view {
	flex-direction: column;

	.counter {
		position: absolute;
		bottom: 0;
		width: 100%;
		padding-left: 40px;
	}

	.option-item {
		padding: 8px 4px;
	}

	.vote-item-wrapper.currentuser {
		border: none;
	}

	.vote-column {
		flex-direction: row-reverse;
		align-items: center;
		max-width: initial;
		position: relative;
		border-top: solid 1px var(--color-border);
		padding: 0;
	}
	&.closed .vote-column {
		padding: 2px 8px;
		&.confirmed {
			margin: 4px 0;
		}
	}

	.vote-table__votes {
		align-items: stretch;
		flex-direction: column;
	}

	.vote-table__users {
		margin: 0
	}

	.vote-table-header-item {
		flex-direction: row;
	}

	.user-item.user.currentuser, .vote-item-wrapper.currentuser {
		display: flex;
	}

	.user-item.user, .vote-item-wrapper {
		display: none;
	}
	.calendar-peek {
		order: 0;
	}
	.calendar-peek__conflict.icon {
		width: 24px;
		height: 24px;
	}
}

</style>
