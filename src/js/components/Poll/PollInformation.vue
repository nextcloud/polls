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
		<Badge icon="icon-mask-md-owner">
			{{ t('polls', 'Poll owner:') }} <UserBubble v-if="poll.owner.userId" :user="poll.owner.userId" :display-name="poll.owner.displayName" />
		</Badge>
		<Badge :icon="accessClass" :title="accessCaption" />
		<Badge icon="icon-mask-md-creation"
			:title="t('polls', 'Created {dateRelative}', { dateRelative: dateCreatedRelative })" />
		<Badge v-if="poll.expire"
			icon="icon-mask-md-closed-poll"
			:title="t('polls', 'Closing: {dateRelative}', {dateRelative: dateExpiryRelative})" />
		<Badge v-if="poll.anonymous"
			icon="icon-mask-md-anonymous-poll"
			:title="t('polls', 'Anonymous poll')" />
		<Badge :icon="resultsClass" :title="resultsCaption" />
		<Badge v-if="countParticipantsVoted && acl.allowSeeResults"
			icon="icon-mask-md-participants"
			:title="n('polls', '%n Participant', '%n Participants', countParticipantsVoted)" />
		<Badge icon="icon-mask-md-options" :title="n('polls', '%n option', '%n options', countOptions)" />
		<Badge v-if="countAllYesVotes" icon="icon-mask-md-yes-votes">
			{{ n('polls', '%n "Yes" vote', '%n "Yes" votes', countAllYesVotes) }}
		</Badge>
		<Badge v-if="countAllNoVotes" icon="icon-mask-md-no-votes">
			{{ n('polls', '%n No vote', '%n "No" votes', countAllNoVotes) }}
		</Badge>
		<Badge v-if="countAllMaybeVotes" icon="icon-mask-md-maybe-votes">
			{{ n('polls', '%n "Maybe" vote', '%n "Maybe" votes', countAllMaybeVotes) }}
		</Badge>
		<Badge icon="icon-mask-md-timezone" :title="t('polls', 'Time zone: {timezoneString}', { timezoneString: currentTimeZone})" />
		<Badge v-if="proposalsAllowed" icon="icon-mask-md-proposals-allowed" :title="proposalsStatus" />
		<div v-if="poll.voteLimit" class="icon-checkmark">
			{{ n('polls', '%n of {maximalVotes} vote left.', '%n of {maximalVotes} votes left.', poll.voteLimit - countVotes('yes'), { maximalVotes: poll.voteLimit }) }}
		</div>
		<div v-if="poll.optionLimit" class="icon-close">
			{{ n('polls', 'Only %n vote per option.', 'Only %n votes per option.', poll.optionLimit) }}
		</div>
		<div v-if="$route.name === 'publicVote' && share.emailAddress" class="icon-mail">
			{{ share.emailAddress }}
		</div>
		<Badge v-if="subscribed"
			icon="icon-mask-md-subscribed"
			:title="t('polls', 'You subscribed to this poll')" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import { UserBubble } from '@nextcloud/vue'
import Badge from '../Base/Badge'

export default {
	name: 'PollInformation',

	components: {
		Badge,
		UserBubble,
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

		resultsClass() {
			if (this.showResults === 'never') {
				return 'icon-mask-md-show-results-never'
			}
			if (this.showResults === 'closed' && !this.closed) {
				return 'icon-mask-md-hide-results-until-closed'
			}
			return 'icon-mask-md-show-results'

		},

		accessCaption() {
			if (this.access === 'private') {
				return t('polls', 'Private poll')
			}
			if (this.important) {
				return t('polls', 'Open accessible and relevant poll')
			}
			return t('polls', 'Open accessible poll')
		},

		accessClass() {
			if (this.access === 'private') {
				return 'icon-mask-md-private-poll'
			}
			if (this.important) {
				return 'icon-mask-md-open-poll'
			}
			return 'icon-mask-md-open-poll'
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
