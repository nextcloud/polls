<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="poll-information">
		<BadgeDiv>
			<template #icon>
				<OwnerIcon />
			</template>
			{{ t('polls', 'Poll owner:') }} <NcUserBubble v-if="pollStore.owner.userId" :user="pollStore.owner.userId" :display-name="pollStore.owner.displayName" />
		</BadgeDiv>
		<BadgeDiv>
			<template #icon>
				<PrivatePollIcon v-if="pollStore.configuration.access === 'private'" />
				<OpenPollIcon v-else />
			</template>
			{{ accessCaption }}
		</BadgeDiv>
		<BadgeDiv>
			<template #icon>
				<CreationIcon />
			</template>
			{{ t('polls', 'Created {dateRelative}', { dateRelative: dateCreatedRelative }) }}
		</BadgeDiv>
		<BadgeDiv v-if="pollStore.configuration.expire">
			<template #icon>
				<ClosedIcon />
			</template>
			{{ t('polls', 'Closing: {dateRelative}', {dateRelative: dateExpiryRelative}) }}
		</BadgeDiv>
		<BadgeDiv v-if="pollStore.configuration.anonymous">
			<template #icon>
				<AnoymousIcon />
			</template>
			{{ t('polls', 'Anonymous poll') }}
		</BadgeDiv>
		<BadgeDiv>
			<template #icon>
				<HideResultsIcon v-if="pollStore.configuration.showResults === 'never'" />
				<ShowResultsOnClosedIcon v-else-if="pollStore.configuration.showResults === 'closed' && pollStore.isClosed" />
				<ShowResultsIcon v-else />
			</template>
			{{ resultsCaption }}
		</BadgeDiv>
		<BadgeDiv v-if="pollStore.countParticipantsVoted && pollStore.permissions.seeResults">
			<template #icon>
				<ParticipantsIcon />
			</template>
			{{ n('polls', '%n Participant', '%n Participants', pollStore.countParticipantsVoted) }}
		</BadgeDiv>
		<BadgeDiv>
			<template #icon>
				<OptionsIcon />
			</template>
			{{ n('polls', '%n option', '%n options', optionsStore.list.length) }}
		</BadgeDiv>
		<BadgeDiv v-if="countAllYesVotes">
			<template #icon>
				<CheckIcon fill-color="#49bc49" />
			</template>
			{{ n('polls', '%n "Yes" vote', '%n "Yes" votes', countAllYesVotes) }}
		</BadgeDiv>
		<BadgeDiv v-if="countAllNoVotes">
			<template #icon>
				<CloseIcon fill-color="#f45573" />
			</template>
			{{ n('polls', '%n No vote', '%n "No" votes', countAllNoVotes) }}
		</BadgeDiv>
		<BadgeDiv v-if="countAllMaybeVotes">
			<template #icon>
				<MaybeIcon />
			</template>
			{{ n('polls', '%n "Maybe" vote', '%n "Maybe" votes', countAllMaybeVotes) }}
		</BadgeDiv>
		<BadgeDiv>
			<template #icon>
				<TimezoneIcon />
			</template>
			{{ t('polls', 'Time zone: {timezoneString}', { timezoneString: currentTimeZone}) }}
		</BadgeDiv>
		<BadgeDiv v-if="pollStore.isProposalAllowed">
			<template #icon>
				<ProposalsAllowedIcon />
			</template>
			{{ proposalsStatus }}
		</BadgeDiv>
		<BadgeDiv v-if="pollStore.configuration.maxVotesPerUser">
			<template #icon>
				<CheckIcon />
			</template>
			{{ n('polls', '{usedVotes} of %n vote left.', '{usedVotes} of %n votes left.', pollStore.configuration.maxVotesPerUser, { maximalVotes: pollStore.configuration.maxVotesPerUser, usedVotes: countUsedVotes }) }}
		</BadgeDiv>
		<BadgeDiv v-if="pollStore.configuration.maxVotesPerOption">
			<template #icon>
				<CloseIcon />
			</template>
			{{ n('polls', 'Only %n vote per option.', 'Only %n votes per option.', pollStore.configuration.maxVotesPerOption) }}
		</BadgeDiv>
		<BadgeDiv v-if="$route.name === 'publicVote' && shareStore.user.emailAddress">
			<template #icon>
				<EmailIcon />
			</template>
			{{ shareStore.user.emailAddress }}
		</BadgeDiv>
		<BadgeDiv v-if="subscriptionStore.subscribed">
			<template #icon>
				<SubscribedIcon />
			</template>
			{{ t('polls', 'You subscribed to this poll') }}
		</BadgeDiv>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import moment from '@nextcloud/moment'
import { NcUserBubble } from '@nextcloud/vue'
import { BadgeDiv } from '../Base/index.js'
import OwnerIcon from 'vue-material-design-icons/Crown.vue'
import SubscribedIcon from 'vue-material-design-icons/Bell.vue'
import ProposalsAllowedIcon from 'vue-material-design-icons/Offer.vue'
import TimezoneIcon from 'vue-material-design-icons/MapClockOutline.vue'
import OptionsIcon from 'vue-material-design-icons/FormatListCheckbox.vue'
import ParticipantsIcon from 'vue-material-design-icons/AccountGroup.vue'
import ShowResultsIcon from 'vue-material-design-icons/Monitor.vue'
import ShowResultsOnClosedIcon from 'vue-material-design-icons/MonitorLock.vue'
import HideResultsIcon from 'vue-material-design-icons/MonitorOff.vue'
import AnoymousIcon from 'vue-material-design-icons/Incognito.vue'
import ClosedIcon from 'vue-material-design-icons/Lock.vue'
import CreationIcon from 'vue-material-design-icons/ClockOutline.vue'
import PrivatePollIcon from 'vue-material-design-icons/Key.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import EmailIcon from 'vue-material-design-icons/Email.vue'
import { MaybeIcon } from '../AppIcons/index.js'
import { t, n } from '@nextcloud/l10n'
import { useShareStore } from '../../stores/share.ts'
import { usePollStore } from '../../stores/poll.ts'
import { useSubscriptionStore } from '../../stores/subscription.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { useVotesStore } from '../../stores/votes.ts'

export default {
	name: 'PollInformation',

	components: {
		BadgeDiv,
		NcUserBubble,
		OwnerIcon,
		SubscribedIcon,
		ProposalsAllowedIcon,
		TimezoneIcon,
		OptionsIcon,
		ParticipantsIcon,
		ShowResultsIcon,
		ShowResultsOnClosedIcon,
		HideResultsIcon,
		AnoymousIcon,
		ClosedIcon,
		CreationIcon,
		PrivatePollIcon,
		OpenPollIcon,
		CheckIcon,
		CloseIcon,
		MaybeIcon,
		EmailIcon,
	},

	computed: {
		...mapStores(useShareStore, usePollStore, useSubscriptionStore, useOptionsStore, useVotesStore),

		proposalsStatus() {
			if (this.pollStore.isProposalsOpen && !this.pollStore.isProposalExpirySet) {
				return t('polls', 'Proposals are allowed')
			}
			if (this.pollStore.isProposalExpirySet && !this.pollStore.isProposalExpired) {
				return t('polls', 'Proposal period ends {timeRelative}', { timeRelative: this.pollStore.proposalsExpireRelative })
			}
			if (this.pollStore.isProposalExpirySet && this.pollStore.isProposalExpired) {
				return t('polls', 'Proposal period ended {timeRelative}', { timeRelative: this.pollStore.proposalsExpireRelative })
			}
			return t('polls', 'No proposals are allowed')
		},

		resultsCaption() {
			if (this.pollStore.configuration.showResults === 'closed' && !this.pollStore.isClosed) {
				return t('polls', 'Results are hidden until closing poll')
			}
			if (this.pollStore.configuration.showResults === 'closed' && this.pollStore.isClosed) {
				return t('polls', 'Results are visible since closing poll')
			}
			if (this.pollStore.configuration.showResults === 'never') {
				return t('polls', 'Results are always hidden')
			}
			return t('polls', 'Results are visible')
		},

		accessCaption() {
			if (this.pollStore.configuration.access === 'private') {
				return t('polls', 'Private poll')
			}
			return t('polls', 'Openly accessible poll')
		},

		dateCreatedRelative() {
			return moment.unix(this.pollStore.status.created).fromNow()
		},

		dateExpiryRelative() {
			return moment.unix(this.pollStore.configuration.expire).fromNow()
		},

		currentTimeZone() {
			return Intl.DateTimeFormat().resolvedOptions().timeZone
		},

		countAllYesVotes() {
			return this.votesStore.countAllVotesByAnswer('yes')
		},

		countAllNoVotes() {
			return this.votesStore.countAllVotesByAnswer('no')
		},

		countAllMaybeVotes() {
			return this.votesStore.countAllVotesByAnswer('maybe')
		},
		countUsedVotes() {
			return this.pollStore.configuration.maxVotesPerUser - this.pollStore.currentUserStatus.yesVotes
		},
	},
	
	methods: {
		t,
		n,
	},
}
</script>

<style lang="scss">
.poll-information {
	display: flex;
	flex-direction: column;
	row-gap: 8px;
}
</style>
