<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="componentClass">
		<OptionItem :option="option" :poll-type="pollStore.type" :display="pollStore.type === 'datePoll' ? 'dateBox' : 'textBox'" />

		<Counter v-if="pollStore.permissions.seeResults"
			:show-maybe="pollStore.permissions.allowMaybe"
			:option="option" />

		<CalendarPeek v-if="showCalendarPeek"
			:focus-trap="false"
			:option="option" />

		<VoteItem v-for="(participant) in pollStore.safeParticipants"
			:key="participant.userId"
			:user-id="participant.userId"
			:option="option" />

		<OptionItemOwner v-if="pollStore.proposalsExist"
			:option="option"
			:avatar-size="24"
			class="owner" />

		<FlexSpacer v-if="pollStore.type === 'datePoll' && viewMode === 'list-view'" />

		<div v-if="pollStore.permissions.edit && pollStore.isClosed" class="action confirm">
			<NcButton :title="confirmButtonCaption"
				:aria-label="confirmButtonCaption"
				type="tertiary"
				@click="optionsStore.confirm(option)">
				<template #icon>
					<UnconfirmIcon v-if="option.confirmed" :size="20" />
					<ConfirmIcon v-else :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcButton } from '@nextcloud/vue'
import Counter from '../Options/Counter.vue'
import OptionItem from '../Options/OptionItem.vue'
import { FlexSpacer } from '../Base/index.js'
import VoteItem from './VoteItem.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import CalendarPeek from '../Calendar/CalendarPeek.vue'
import OptionItemOwner from '../Options/OptionItemOwner.vue'
import { t } from '@nextcloud/l10n'
import { getCurrentUser } from '@nextcloud/auth'
import { usePollStore } from '../../stores/poll.ts'
import { usePreferencesStore } from '../../stores/preferences.ts'
import { useAclStore } from '../../stores/acl.ts'
import { useVotesStore } from '../../stores/votes.ts'
import { useOptionsStore } from '../../stores/options.ts'

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
		...mapStores(usePollStore, usePreferencesStore, useAclStore, useVotesStore, useOptionsStore),

		componentClass() {
			const classList = ['vote-column']
			if (this.option.locked) {
				classList.push('locked')
			}

			if (this.option.confirmed && this.pollStore.isClosed) {
				classList.push('confirmed')
			}

			classList.push(this.ownAnswer)

			return classList
		},

		confirmButtonCaption() {
			return this.option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')
		},

		ownAnswer() {
			return this.votesStore.getVote({
				userId: this.aclStore.currentUser.userId,
				option: this.option,
			}).answer
		},

		showCalendarPeek() {
			return this.pollStore.type === 'datePoll' && getCurrentUser() && this.preferencesStore.calendarPeek
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
