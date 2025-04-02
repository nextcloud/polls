<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
				variant="tertiary"
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
	}

	&.locked:hover {
		background-color: var(--color-polls-background-no);
	}
}

</style>
