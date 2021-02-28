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
	<Popover>
		<div slot="trigger">
			<Actions>
				<ActionButton icon="icon-info">
					{{ t('polls', 'Poll informations') }}
				</ActionButton>
			</Actions>
		</div>
		<div class="poll-information">
			<div class="owner">
				{{ t('polls', 'Poll owner: ') }} <UserBubble v-if="poll.owner" :user="poll.owner" :display-name="poll.ownerDisplayName" />
			</div>
			<div class="created">
				{{ t('polls', 'Created {dateRelative}.', { dateRelative: dateCreatedRelative }) }}
			</div>
			<div v-if="poll.expire" class="closed">
				{{ t('polls', 'Closing: ') }} {{ dateExpiryRelative }}
			</div>
			<div v-if="poll.anonymous" class="anonymous">
				{{ t('polls', 'Anonymous poll') }}
			</div>
			<div class="timezone">
				{{ t('polls', 'Time zone: ') }} {{ currentTimeZone }}
			</div>
			<div v-if="poll.voteLimit" class="vote-limit">
				{{ n('polls', '%n of {maximalVotes} vote left.', '%n of {maximalVotes} votes left.', poll.voteLimit - countYesVotes, { maximalVotes: poll.voteLimit }) }}
			</div>
			<div v-if="poll.optionLimit" class="option-limit">
				{{ n('polls', 'Only %n vote per option.', 'Only %n votes per option.', poll.optionLimit) }}
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
			acl: state => state.poll.acl,
			poll: state => state.poll,
		}),

		...mapGetters({
			participantsVoted: 'poll/participantsVoted',
			closed: 'poll/closed',
			confirmedOptions: 'options/confirmed',
			countYesVotes: 'votes/countYesVotes',
		}),

		voteLimitReached() {
			return (this.poll.voteLimit > 0 && this.countYesVotes >= this.poll.voteLimit)
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
		.owner {
			background-image: var(--icon-user-000);
		}

		.created {
			background-image: var(--icon-star-000);
		}

		.closed {
			background-image: var(--icon-polls-closed);
		}

		.anonymous {
			background-image: var(--icon-polls-anonymous);
		}

		.timezone {
			background-image: var(--icon-clock);
		}

		.vote-limit {
			background-image: var(--icon-checkmark-000);
		}
		.option-limit {
			background-image: var(--icon-close-000);
		}
	}

</style>
