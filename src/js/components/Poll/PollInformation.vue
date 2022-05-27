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
	<div class="poll-information">
		<Badge>
			<template #icon>
				<OwnerIcon />
			</template>
			{{ t('polls', 'Poll owner:') }} <UserBubble v-if="poll.owner.userId" :user="poll.owner.userId" :display-name="poll.owner.displayName" />
		</Badge>
		<Badge>
			<template #icon>
				<PrivatePollIcon v-if="access === 'private'" />
				<OpenPollIcon v-else />
			</template>
			{{ accessCaption }}
		</Badge>
		<Badge>
			<template #icon>
				<CreationIcon />
			</template>
			{{ t('polls', 'Created {dateRelative}', { dateRelative: dateCreatedRelative }) }}
		</Badge>
		<Badge v-if="poll.expire">
			<template #icon>
				<ClosedIcon />
			</template>
			{{ t('polls', 'Closing: {dateRelative}', {dateRelative: dateExpiryRelative}) }}
		</Badge>
		<Badge v-if="poll.anonymous">
			<template #icon>
				<AnoymousIcon />
			</template>
			{{ t('polls', 'Anonymous poll') }}
		</Badge>
		<Badge>
			<template #icon>
				<HideResultsIcon v-if="showResults === 'never'" />
				<ShowResultsOnClosedIcon v-else-if="showResults === 'closed' && closed" />
				<ShowResultsIcon v-else />
			</template>
			{{ resultsCaption }}
		</Badge>
		<Badge v-if="countParticipantsVoted && acl.allowSeeResults">
			<template #icon>
				<ParticipantsIcon />
			</template>
			{{ n('polls', '%n Participant', '%n Participants', countParticipantsVoted) }}
		</Badge>
		<Badge>
			<template #icon>
				<OptionsIcon />
			</template>
			{{ n('polls', '%n option', '%n options', countOptions) }}
		</Badge>
		<Badge v-if="countAllYesVotes">
			<template #icon>
				<CheckIcon fill-color="#49bc49" />
			</template>
			{{ n('polls', '%n "Yes" vote', '%n "Yes" votes', countAllYesVotes) }}
		</Badge>
		<Badge v-if="countAllNoVotes">
			<template #icon>
				<CloseIcon fill-color="#f45573" />
			</template>
			{{ n('polls', '%n No vote', '%n "No" votes', countAllNoVotes) }}
		</Badge>
		<Badge v-if="countAllMaybeVotes">
			<template #icon>
				<MaybeIcon />
			</template>
			{{ n('polls', '%n "Maybe" vote', '%n "Maybe" votes', countAllMaybeVotes) }}
		</Badge>
		<Badge>
			<template #icon>
				<TimezoneIcon />
			</template>
			{{ t('polls', 'Time zone: {timezoneString}', { timezoneString: currentTimeZone}) }}
		</Badge>
		<Badge v-if="proposalsAllowed">
			<template #icon>
				<ProposalsAllowedIcon />
			</template>
			{{ proposalsStatus }}
		</Badge>
		<Badge v-if="poll.voteLimit">
			<template #icon>
				<CheckIcon />
			</template>
			{{ n('polls', '%n of {maximalVotes} vote left.', '%n of {maximalVotes} votes left.', poll.voteLimit - countVotes('yes'), { maximalVotes: poll.voteLimit }) }}
		</Badge>
		<Badge v-if="poll.optionLimit">
			<template #icon>
				<CloseIcon />
			</template>
			{{ n('polls', 'Only %n vote per option.', 'Only %n votes per option.', poll.optionLimit) }}
		</Badge>
		<Badge v-if="$route.name === 'publicVote' && share.emailAddress">
			<template #icon>
				<EmailIcon />
			</template>
			{{ share.emailAddress }}
		</Badge>
		<Badge v-if="subscribed">
			<template #icon>
				<SubscribedIcon />
			</template>
			{{ t('polls', 'You subscribed to this poll') }}
		</Badge>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import { UserBubble } from '@nextcloud/vue'
import Badge from '../Base/Badge.vue'
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
import MaybeIcon from '../AppIcons/MaybeIcon.vue'

export default {
	name: 'PollInformation',

	components: {
		Badge,
		UserBubble,
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
		...mapState({
			share: (state) => state.share,
			acl: (state) => state.poll.acl,
			poll: (state) => state.poll,
			subscribed: (state) => state.subscription.subscribed,
			showResults: (state) => state.poll.showResults,
			important: (state) => state.poll.important,
			access: (state) => state.poll.access,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			confirmedOptions: 'options/confirmed',
			countOptions: 'options/count',
			countParticipantsVoted: 'poll/countParticipantsVoted',
			countVotes: 'votes/countVotes',
			countAllVotes: 'votes/countAllVotes',
			proposalsAllowed: 'poll/proposalsAllowed',
			proposalsExpirySet: 'poll/proposalsExpirySet',
			proposalsExpired: 'poll/proposalsExpired',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
			proposalsOpen: 'poll/proposalsOpen',
			displayResults: 'polls/displayResults',
		}),

		proposalsStatus() {
			if (this.proposalsOpen && !this.proposalsExpirySet) {
				return t('polls', 'Proposals are allowed')
			}
			if (this.proposalsExpirySet && !this.proposalsExpired) {
				return t('polls', 'Proposal period ends {timeRelative}', { timeRelative: this.proposalsExpireRelative })
			}
			if (this.proposalsExpirySet && this.proposalsExpired) {
				return t('polls', 'Proposal period ended {timeRelative}', { timeRelative: this.proposalsExpireRelative })
			}
			return t('polls', 'No proposals are allowed')
		},

		resultsCaption() {
			if (this.showResults === 'closed' && !this.closed) {
				return t('polls', 'Results are hidden until closing poll')
			}
			if (this.showResults === 'closed' && this.closed) {
				return t('polls', 'Results are visible since closing poll')
			}
			if (this.showResults === 'never') {
				return t('polls', 'Results are always hidden')
			}
			return t('polls', 'Results are visible')
		},

		accessCaption() {
			if (this.access === 'private') {
				return t('polls', 'Private poll')
			}
			if (this.important) {
				return t('polls', 'Openly accessible and relevant poll')
			}
			return t('polls', 'Openly accessible poll')
		},

		dateCreatedRelative() {
			return moment.unix(this.poll.created).fromNow()
		},

		dateExpiryRelative() {
			return moment.unix(this.poll.expire).fromNow()
		},

		currentTimeZone() {
			return Intl.DateTimeFormat().resolvedOptions().timeZone
		},

		countAllYesVotes() {
			return this.countAllVotes('yes')
		},

		countAllNoVotes() {
			return this.countAllVotes('no')
		},

		countAllMaybeVotes() {
			return this.countAllVotes('maybe')
		},

	},
}
</script>
<style lang="scss">
	.poll-information {
		padding: 8px;
		> div {
			background-position: 0 4px;
			background-repeat: no-repeat;
			opacity: 0.7;
			margin: 8px 0 4px 0;
			padding-left: 24px;
		}
	}

</style>
