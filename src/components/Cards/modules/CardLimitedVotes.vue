<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :heading="t('polls', 'Limited votes.')" :type="cardType">
		<ul>
			<li v-if="pollStore.configuration.maxVotesPerOption">
				{{ n('polls', '%n vote is allowed per option.', '%n votes are allowed per option.', pollStore.configuration.maxVotesPerOption) }}
			</li>
			<li v-if="pollStore.configuration.maxVotesPerUser">
				{{ n('polls', '%n vote is allowed per participant.', '%n votes are allowed per participant.', pollStore.configuration.maxVotesPerUser) }}
				{{ n('polls', 'You have %n vote left.', 'You have %n votes left.', votesLeft) }}
			</li>
			<div v-if="pollStore.currentUserStatus.orphanedVotes && pollStore.configuration.maxVotesPerUser">
				<b>{{ orphanedVotesText }}</b>
			</div>
		</ul>

		<template v-if="pollStore.currentUserStatus.orphanedVotes && pollStore.configuration.maxVotesPerUser" #button>
			<ActionDeleteOrphanedVotes />
		</template>
	</CardDiv>
</template>

<script>
import { mapStores } from 'pinia'
import { CardDiv } from '../../Base/index.js'
import ActionDeleteOrphanedVotes from '../../Actions/modules/ActionDeleteOrphanedVotes.vue'
import { t, n } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

export default {
	name: 'CardLimitedVotes',
	components: {
		CardDiv,
		ActionDeleteOrphanedVotes,
	},

	computed: {
		...mapStores(usePollStore),

		orphanedVotesText() {
			return n(
				'polls',
				'%n orphaned vote of a probaly deleted option is possibly blocking your vote limit.',
				'%n orphaned votes of probaly deleted options are possibly blocking your vote limit.',
				this.pollStore.currentUserStatus.orphanedVotes)
		},

		votesLeft() {
			return (this.pollStore.configuration.maxVotesPerUser - this.pollStore.currentUserStatus.yesVotes) > 0
				? this.pollStore.configuration.maxVotesPerUser - this.pollStore.currentUserStatus.yesVotes
				: 0
		},

		cardType() {
			return this.pollStore.configuration.maxVotesPerUser && this.votesLeft < 1 ? 'error' : 'info'
		},
	},

	methods: {
		t,
		n,
	},
}
</script>
