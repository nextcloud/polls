<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
import { mapState } from 'vuex'
import { CardDiv } from '../../Base/index.js'
import ActionDeleteOrphanedVotes from '../../Actions/modules/ActionDeleteOrphanedVotes.vue'

export default {
	name: 'CardLimitedVotes',
	components: {
		CardDiv,
		ActionDeleteOrphanedVotes,
	},

	computed: {
		...mapState({
			orphanedVotes: (state) => state.poll.currentUserStatus.orphanedVotes,
			yesVotes: (state) => state.poll.currentUserStatus.yesVotes,
			maxVotesPerOption: (state) => state.poll.configuration.maxVotesPerOption,
			maxVotesPerUser: (state) => state.poll.configuration.maxVotesPerUser,
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
}
</script>
