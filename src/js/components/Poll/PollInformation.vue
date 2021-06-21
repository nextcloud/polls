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
	<Popover>
		<div slot="trigger">
			<Actions>
				<ActionButton icon="icon-info">
					{{ t('polls', 'Poll informations') }}
				</ActionButton>
			</Actions>
		</div>
		<div class="poll-information">
			<div class="icon-user">
				{{ t('polls', 'Poll owner:') }} <UserBubble v-if="poll.owner" :user="poll.owner" :display-name="poll.ownerDisplayName" />
			</div>
			<div :class="accessClass">
				{{ accessCaption }}
			</div>
			<div class="icon-star">
				{{ t('polls', 'Created {dateRelative}', { dateRelative: dateCreatedRelative }) }}
			</div>
			<div v-if="poll.expire" class="icon-polls-closed">
				{{ t('polls', 'Closing: {dateRelative}', {dateRelative: dateExpiryRelative}) }}
			</div>
			<div v-if="poll.anonymous" class="icon-polls-anonymous">
				{{ t('polls', 'Anonymous poll') }}
			</div>
			<div :class="resultsClass">
				{{ resultsCaption }}
			</div>
			<div v-if="countParticipantsVoted && acl.allowSeeResults" class="icon-user">
				{{ n('polls', '%n Participant', '%n Participants', countParticipantsVoted) }}
			</div>
			<div class="icon-polls-unconfirmed">
				{{ n('polls', '%n option', '%n options', countOptions) }}
			</div>
			<div v-if="countAllYesVotes" class="icon-polls-yes">
				{{ n('polls', '%n yes vote', '%n yes votes', countAllYesVotes) }}
			</div>
			<div v-if="countAllNoVotes" class="icon-polls-no">
				{{ n('polls', '%n no vote', '%n no votes', countAllNoVotes) }}
			</div>
			<div v-if="countAllMaybeVotes" class="icon-polls-maybe">
				{{ n('polls', '%n maybe vote', '%n maybe votes', countAllMaybeVotes) }}
			</div>
			<div class="icon-timezone">
				{{ t('polls', 'Time zone: {timezoneString}', { timezoneString: currentTimeZone}) }}
			</div>
			<div v-if="proposalsAllowed" class="icon-add">
				{{ proposalsStatus }}
			</div>
			<div v-if="poll.voteLimit" class="icon-checkmark">
				{{ n('polls', '%n of {maximalVotes} vote left.', '%n of {maximalVotes} votes left.', poll.voteLimit - countVotes('yes'), { maximalVotes: poll.voteLimit }) }}
			</div>
			<div v-if="poll.optionLimit" class="icon-close">
				{{ n('polls', 'Only %n vote per option.', 'Only %n votes per option.', poll.optionLimit) }}
			</div>
			<div v-if="$route.name === 'publicVote' && share.emailAddress" class="icon-mail">
				{{ share.emailAddress }}
			</div>
			<div v-if="subscribed" class="icon-sound">
				{{ t('polls', 'You subscribed to this poll') }}
			</div>
		</div>
	</Popover>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import { Actions, ActionButton, Popover, UserBubble } from '@nextcloud/vue'

export default {
	name: 'PollInformation',

	components: {
		Actions,
		ActionButton,
		Popover,
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
				return t('polls', 'Option proposals allowed')
			}
			if (this.proposalsExpirySet && !this.proposalsExpired) {
				return t('polls', 'Option proposal term ends {timeRelative}', { timeRelative: this.proposalsExpireRelative })
			}
			if (this.proposalsExpirySet && this.proposalsExpired) {
				return t('polls', 'Option proposal term ended {timeRelative}', { timeRelative: this.proposalsExpireRelative })
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
			if (this.access === 'hidden') {
				return t('polls', 'Access only for invited persons')
			}
			if (this.important) {
				return t('polls', 'Relevant and accessible for all users')
			}
			return t('polls', 'Access for all users')
		},

		accessClass() {
			if (this.access === 'hidden') {
				return 'icon-polls-hidden-poll'
			}
			if (this.important) {
				return 'icon-polls-public-poll'
			}
			return 'icon-polls-public-poll'
		},

		resultsClass() {
			if (this.showResults === 'never' || (this.showResults === 'closed' && !this.closed)) {
				return 'icon-polls-hidden'
			}
			return 'icon-polls-visible'

		},

		voteLimitReached() {
			return (this.poll.voteLimit > 0 && this.countVotes('yes') >= this.poll.voteLimit)
		},

		dateCreatedRelative() {
			return moment.unix(this.poll.created).fromNow()
		},

		dateCreatedString() {
			return moment.unix(this.poll.created).format('LLLL')
		},

		dateExpiryString() {
			return moment.unix(this.poll.expire).format('LLLL')
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
