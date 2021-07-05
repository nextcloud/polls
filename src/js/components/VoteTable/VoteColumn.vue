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
	<div :class="['vote-column', { 'confirmed' : option.confirmed && closed }]">
		<VoteTableHeaderItem :option="option" :view-mode="viewMode" />

		<Confirmation v-if="option.confirmed && closed" :option="option" />

		<Counter v-else-if="acl.allowSeeResults"
			:show-maybe="!!poll.allowMaybe"
			:option="option" />
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
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { Actions, ActionButton } from '@nextcloud/vue'
import Counter from '../Options/Counter'
import VoteItem from './VoteItem'
import VoteTableHeaderItem from './VoteTableHeaderItem'
import { confirmOption } from '../../mixins/optionMixins'

export default {
	name: 'VoteColumn',
	components: {
		Actions,
		ActionButton,
		CalendarPeek: () => import('../Calendar/CalendarPeek'),
		Counter,
		Confirmation: () => import('../Options/Confirmation'),
		VoteTableHeaderItem,
		VoteItem,
		OptionItemOwner: () => import('../Options/OptionItemOwner'),
	},

	mixins: [confirmOption],

	props: {
		viewMode: {
			type: String,
			default: 'table-view',
		},
		option: {
			type: Object,
			default: undefined,
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
			settings: (state) => state.settings.user,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			participants: 'poll/safeParticipants',
			proposalsExist: 'options/proposalsExist',
		}),

	},
}
</script>
