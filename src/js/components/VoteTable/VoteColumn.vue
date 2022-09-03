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
	<div :class="componentClass">
		<OptionItem :option="option" :poll-type="poll.type" :display="poll.type === 'datePoll' ? 'dateBox' : 'textBox'" />

		<Counter v-if="acl.allowSeeResults"
			:show-maybe="!!poll.allowMaybe"
			:option="option" />

		<CalendarPeek v-if="showCalendarPeek" :option="option" />

		<VoteItem v-for="(participant) in participants"
			:key="participant.userId"
			:user-id="participant.userId"
			:option="option"
			:locked="isLocked"
			:confirmed="isConfirmed" />

		<OptionItemOwner v-if="proposalsExist"
			:option="option"
			:avatar-size="24"
			class="owner" />

		<FlexSpacer v-if="poll.type === 'datePoll' && viewMode === 'list-view'" />

		<div v-if="acl.allowEdit && closed" class="action confirm">
			<NcButton v-tooltip="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
				type="tertiary"
				@click="confirmOption(option)">
				<template #icon>
					<UnconfirmIcon v-if="option.confirmed" :size="20" />
					<ConfirmIcon v-else :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import Counter from '../Options/Counter.vue'
import OptionItem from '../Options/OptionItem.vue'
import FlexSpacer from '../Base/FlexSpacer.vue'
import VoteItem from './VoteItem.vue'
import { confirmOption } from '../../mixins/optionMixins.js'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'

export default {
	name: 'VoteColumn',
	components: {
		ConfirmIcon,
		UnconfirmIcon,
		Counter,
		OptionItem,
		FlexSpacer,
		VoteItem,
		NcButton,
		CalendarPeek: () => import('../Calendar/CalendarPeek.vue'),
		OptionItemOwner: () => import('../Options/OptionItemOwner.vue'),
	},

	mixins: [confirmOption],

	props: {
		option: {
			type: Object,
			default: undefined,
		},
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
			acl: (state) => state.poll.acl,
			poll: (state) => state.poll,
			settings: (state) => state.settings.user,
			currentUser: (state) => state.poll.acl.userId,
			isVoteLimitExceeded: (state) => state.poll.acl.isVoteLimitExceeded,
			voteLimit: (state) => state.poll.voteLimit,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			getVote: 'votes/getVote',
			participants: 'poll/safeParticipants',
			proposalsExist: 'options/proposalsExist',
		}),

		componentClass() {
			const classList = ['vote-column']
			if (this.isLocked) {
				classList.push('locked')
			}

			if (this.option.confirmed && this.closed) {
				classList.push('confirmed')
			}

			classList.push(this.ownAnswer)

			return classList
		},

		isConfirmed() {
			return !!(this.option.confirmed && this.closed)
		},

		ownAnswer() {
			return this.getVote({
				userId: this.currentUser,
				option: this.option,
			}).answer
		},

		isLocked() {
			return (this.option.computed.isBookedUp || this.isVoteLimitExceeded)
				&& !this.closed
				&& this.ownAnswer !== 'yes'
				&& this.ownAnswer !== 'maybe'
		},

		showCalendarPeek() {
			return this.poll.type === 'datePoll' && this.getCurrentUser() && this.settings.calendarPeek
		},
	},
}
</script>
