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
	<CardDiv :heading="t('polls', 'Limited votes.')" :type="cardType">
		<ul>
			<li v-if="maxVotesPerOption">
				{{ n('polls', '%n vote is allowed per option.', '%n votes are allowed per option.', maxVotesPerOption) }}
			</li>
			<li v-if="maxVotesPerUser">
				{{ n('polls', '%n vote is allowed per participant.', '%n votes are allowed per participant.', maxVotesPerUser) }}
				{{ n('polls', 'You have %n vote left.', 'You have %n votes left.', votesLeft) }}
			</li>
			<div v-if="orphanedVotes && maxVotesPerUser">
				<b>{{ orphanedVotesText }}</b>
			</div>
		</ul>

		<template v-if="orphanedVotes && maxVotesPerUser" #button>
			<ActionDeleteOrphanedVotes />
		</template>
	</CardDiv>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import { CardDiv } from '../../Base/index.js'

export default {
	name: 'CardLimitedVotes',
	components: {
		CardDiv,
		ActionDeleteOrphanedVotes: () => import('../../Actions/modules/ActionDeleteOrphanedVotes.vue'),
	},

	computed: {
		...mapState({
			orphanedVotes: (state) => state.poll.currentUserStatus.orphanedVotes,
			yesVotes: (state) => state.poll.currentUserStatus.yesVotes,
			maxVotesPerOption: (state) => state.poll.limits.maxVotesPerOption,
			maxVotesPerUser: (state) => state.poll.limits.maxVotesPerUser,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
		}),

		orphanedVotesText() {
			return n(
				'polls',
				'%n orphaned vote of a probaly deleted option is possibly blocking your vote limit.',
				'%n orphaned votes of probaly deleted options are possibly blocking your vote limit.',
				this.orphanedVotes)
		},

		votesLeft() {
			return (this.maxVotesPerUser - this.yesVotes) > 0
				? this.maxVotesPerUser - this.yesVotes
				: 0
		},

		cardType() {
			return this.maxVotesPerUser && this.votesLeft < 1 ? 'error' : 'info'
		},

	},

	methods: {
		...mapActions({
			deleteOrphanedVotes: 'votes/removeOrphanedVotes',
		}),
	},
}
</script>
