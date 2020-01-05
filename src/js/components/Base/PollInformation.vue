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
		<h3>
			<UserBubble :user="poll.owner" :display-name="poll.owner" />
			{{ t('polls', ' started this poll on %n. ', 1, moment.unix(poll.created).format('LLLL')) }}
			<span v-if="expired">{{ t('polls', 'Voting is no more possible, because this poll expired since %n', 1, moment.unix(poll.expire).format('LLLL')) }}</span>
			<span v-if="!expired && poll.expire && acl.allowVote">{{ t('polls', 'You can place your vote until %n. ',1, moment.unix(poll.expire).format('LLLL')) }}</span>
			<span v-if="poll.anonymous">{{ t('polls', 'The names of other participants is hidden, as this is a anonymous poll. ') }}</span>
			<span>{{ t('polls', '%n voters participated in this poll until now.', 1, participants.length) }}</span>
		</h3>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'PollInformation',

	computed: {
		...mapState({
			acl: state => state.acl,
			poll: state => state.poll
		}),

		...mapGetters([
			'participants',
			'expired'
		])
	}
}
</script>
