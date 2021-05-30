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
				<UserMenu v-if="participant.userId === acl.userId" />

				<ActionDelete v-if="acl.allowEdit"
					:title="t('polls', 'Delete votes')"
					@delete="removeUser(participant.userId)" />
			</UserItem>

			<div v-if="proposalsExist" class="owner" />

			<div v-if="acl.allowEdit && closed" class="confirm" />
		</div>

		<transition-group name="list" tag="div" class="vote-table__votes">
			<div v-for="(option) in options" :key="option.id" :class="['vote-column', { 'confirmed' : option.confirmed && closed }]">
				<VoteTableHeaderItem :option="option" :view-mode="viewMode" />

				<Confirmation v-if="option.confirmed && closed" :option="option" />

				<Counter v-else-if="acl.allowSeeResults"
					:show-maybe="!!poll.allowMaybe"
					:option="option"
					:counter-style="viewMode === 'table-view' ? 'iconStyle' : 'barStyle'"
					:show-no="viewMode === 'list-view'" />
				<CalendarPeek v-if="poll.type === 'datePoll' && getCurrentUser() && settings.calendarPeek" :option="option" />

				<VoteItem v-for="(participant) in participants"
					:key="participant.userId"
					:user-id="participant.userId"
					:option="option" />

				<OptionItemOwner v-if="proposalsExist" :option="option" class="owner" />

				<Actions v-if="acl.allowEdit && closed" class="action confirm">
					<ActionButton v-if="closed"
						:icon="option.confirmed ? 'icon-polls-confirmed' : 'icon-polls-unconfirmed'"
						@click="confirmOption(option)">
						{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
					</ActionButton>
				</Actions>
			</div>
		</transition-group>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { showSuccess } from '@nextcloud/dialogs'
import { Actions, ActionButton } from '@nextcloud/vue'
import ActionDelete from '../Actions/ActionDelete'
import CalendarPeek from '../Calendar/CalendarPeek'
import Counter from '../Options/Counter'
import Confirmation from '../Options/Confirmation'
import OptionItemOwner from '../Options/OptionItemOwner'
import UserMenu from '../User/UserMenu'
import VoteItem from './VoteItem'
import VoteTableHeaderItem from './VoteTableHeaderItem'
import { confirmOption } from '../../mixins/optionMixins'

export default {
	name: 'VoteTable',
	components: {
		Actions,
		ActionButton,
		ActionDelete,
		CalendarPeek,
		Counter,
		Confirmation,
		UserMenu,
		VoteTableHeaderItem,
		VoteItem,
		OptionItemOwner,
	},

	mixins: [confirmOption],

	props: {
		viewMode: {
			type: String,
			default: 'table-view',
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
			acl: (state) => state.poll.acl,
			poll: (state) => state.poll,
			share: (state) => state.share,
			settings: (state) => state.settings.user,
		}),

		...mapGetters({
			hideResults: 'poll/hideResults',
			closed: 'poll/closed',
			participants: 'poll/participants',
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

	.user-item, .vote-item {
		flex: 0 0 auto;
		height: 3.5em;
		order: 10;
		line-height: 3.5em;
		padding: 4px 1px;
		border-top: solid 1px var(--color-border-dark);
		&.currentuser {
			order:5;
		}
	}

	.vote-table__users {
		display: flex;
		flex-direction: column;
		overflow-x: scroll;
		// max-width: 245px;
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
		&>div {
			display: flex;
			justify-content: center;
			align-items: center;
		}
	}

	&.closed .vote-column {
		// padding: 8px 2px;
		&.confirmed {
			order: 1;
			border-radius: 10px;
			border: 1px solid var(--color-polls-foreground-yes);
			background-color: var(--color-polls-background-yes);
			margin: 0 4px;
		}
	}

	.vote-table-header-item {
		flex: 1;
		padding: 0 8px;
		order: 1;
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
		height: 3.5em;
		line-height: 3.5em;
		min-width: 56px;
		order: 19;
	}

	.spacer {
		flex: 1;
		order: 1;
	}

	&.table-view {
		.vote-table__users::after, .vote-column::after {
			content: '';
			height: 8px;
			order: 99;
		}

		.user-item {
			max-width: 245px;
		}

		.option-item .option-item__option--text {
			text-align: center;
		}
	}

	&.list-view {
		flex-direction: column;

		&.closed {
			.counter {
				padding-left: 60px;
			}

			.vote-item:not(.confirmed) {
				background-color: var(--color-main-background);
				&.no > .icon {
					background-image: var(--icon-polls-no)
				}
			}

			.vote-column {
				padding: 2px 8px;
				&.confirmed {
					margin: 4px 0;
				}
			}
		}

		.vote-table__users .confirm {
			display: none;
		}

		.vote-column {
			flex-direction: row-reverse;
			align-items: center;
			max-width: initial;
			position: relative;
			border-top: solid 1px var(--color-border);
			padding: 0;
		}

		.vote-table__users {
			margin: 0
		}

		.user-item.user:not(.currentuser), .vote-item:not(.currentuser) {
			display: none;
		}

		.vote-table__votes {
			align-items: stretch;
			flex-direction: column;
		}

		.vote-table-header-item {
			flex-direction: row;
			.option-item {
				padding: 8px 4px;
			}
		}

		.counter {
			position: absolute;
			bottom: 0;
			width: 100%;
			padding-left: 44px;
		}

		.vote-item.currentuser {
			border: none;
		}

		.owner {
			order: 0;
		}

		.calendar-peek {
			order: 0;
		}
		.calendar-peek__conflict.icon {
			width: 24px;
			height: 24px;
		}
	}
}



</style>
