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

<template>
	<div :class="componentClass">
		<OptionItem :option="option" :poll-type="pollType" :display="pollType === 'datePoll' ? 'dateBox' : 'textBox'" />

		<Counter v-if="permissions.seeResults"
			:show-maybe="permissions.allowMaybe"
			:option="option" />

		<CalendarPeek v-if="showCalendarPeek"
			:focus-trap="false"
			:option="option" />

		<VoteItem v-for="(participant) in participants"
			:key="participant.userId"
			:user-id="participant.userId"
			:option="option" />

		<OptionItemOwner v-if="proposalsExist"
			:option="option"
			:avatar-size="24"
			class="owner" />

		<FlexSpacer v-if="pollType === 'datePoll' && viewMode === 'list-view'" />

		<div v-if="permissions.edit && isPollClosed" class="action confirm">
			<NcButton :title="confirmButtonCaption"
				:aria-label="confirmButtonCaption"
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
import { FlexSpacer } from '../Base/index.js'
import VoteItem from './VoteItem.vue'
import { confirmOption } from '../../mixins/optionMixins.js'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import CalendarPeek from '../Calendar/CalendarPeek.vue'
import OptionItemOwner from '../Options/OptionItemOwner.vue'

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
		CalendarPeek,
		OptionItemOwner,
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
			permissions: (state) => state.poll.permissions,
			pollType: (state) => state.poll.type,
			settings: (state) => state.settings.user,
			currentUser: (state) => state.acl.currentUser,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
			getVote: 'votes/getVote',
			participants: 'poll/safeParticipants',
			proposalsExist: 'options/proposalsExist',
		}),

		componentClass() {
			const classList = ['vote-column']
			if (this.option.locked) {
				classList.push('locked')
			}

			if (this.option.confirmed && this.isPollClosed) {
				classList.push('confirmed')
			}

			classList.push(this.ownAnswer)

			return classList
		},

		confirmButtonCaption() {
			return this.option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')
		},

		ownAnswer() {
			return this.getVote({
				userId: this.currentUser.userId,
				option: this.option,
			}).answer
		},

		showCalendarPeek() {
			return this.pollType === 'datePoll' && this.getCurrentUser() && this.settings.calendarPeek
		},
	},
}
</script>

<style lang="scss">
.vote-style-beta-510 .vote-column {
	border-radius: var(--border-radius-large);

	&:hover {
		background-color: var(--color-background-dark);
		// box-shadow: 3px 3px 9px var(--color-background-darker);
	}

	&.locked:hover {
		background-color: var(--color-polls-background-no);
	}
}

</style>
