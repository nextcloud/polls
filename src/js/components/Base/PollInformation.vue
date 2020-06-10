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
	<div class="poll-information">
		<UserBubble v-if="poll.owner" :user="poll.owner" :display-name="poll.ownerDisplayName" />
		{{ t('polls', 'started this poll on %n. ', 1, dateCreatedString) }}

		<span v-if="expired && confirmedOptions.length"> {{ t('polls', 'This poll expired on {dateString}. The confirmed options are marked below.', { dateString: dateExpiryString }) }} </span>

		<span v-if="expired && !confirmedOptions.length"> {{ t('polls', 'This poll expired on {dateString}, but there are no confirmed options until now.', { dateString: dateExpiryString }) }} </span>

		<span v-if="expired && !confirmedOptions.length && acl.allowEdit"> {{ t('polls', 'You can confirm your favorites now in the options tab in the sidebar.', { dateString: dateExpiryString }) }} </span>

		<span v-if="!expired && poll.expire && acl.allowVote">{{ t('polls', 'You can place your vote until {dateString}.', { dateString: dateExpiryString }) }} </span>

		<span v-if="poll.anonymous">{{ t('polls', 'The names of other participants are hidden, as this is an anonymous poll. ') }} </span>

		<span v-if="!acl.allowSeeResults">{{ t('polls', 'Results are hidden. ') }}</span>

		<span v-if="!acl.allowSeeResults && poll.showResults === 'expired'">{{ t('polls', 'They will be revealed after the poll is expired. ') }}</span>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import { UserBubble } from '@nextcloud/vue'

export default {
	name: 'PollInformation',

	components: {
		UserBubble,
	},

	computed: {
		...mapState({
			acl: state => state.acl,
			poll: state => state.poll,
		}),

		...mapGetters([
			'participantsVoted',
			'expired',
			'confirmedOptions',
		]),
		dateCreatedString() {
			return moment.unix(this.poll.created).format('LLLL')
		},
		dateExpiryString() {
			return moment.unix(this.poll.expire).format('LLLL')
		},
	},
}
</script>
