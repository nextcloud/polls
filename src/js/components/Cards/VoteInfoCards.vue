<!--
  - @copyright Copyright (c) 2024 René Gieling <github@dartcafe.de>
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
	<div class="info-section">
		<CardUnpublishedPoll v-if="showUnpublishedPollCard" />
		<CardAddProposals v-if="showAddProposalsCard" />
		<CardLimitedVotes v-if="showLimitCard" />
		<CardClosedPoll v-if="showClosedCard" />
		<CardSendConfirmations v-if="showSendConfirmationsCard" />
		<CardLocked v-if="isLocked" />
		<CardRegister v-if="showRegisterCard" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { CardAddProposals, CardClosedPoll, CardLimitedVotes, CardLocked, CardRegister, CardSendConfirmations, CardUnpublishedPoll } from './index.js'

export default {
	name: 'VoteInfoCards',

	components: {
		CardAddProposals,
		CardClosedPoll,
		CardLimitedVotes,
		CardLocked,
		CardRegister,
		CardSendConfirmations,
		CardUnpublishedPoll,

	},

	computed: {
		...mapState({
			pollAccess: (state) => state.poll.access,
			pollId: (state) => state.poll.id,
			allowEdit: (state) => state.poll.acl.permissions.edit,
			allowVote: (state) => state.poll.acl.permissions.vote,
			allowAddOptions: (state) => state.poll.acl.permissions.addOptions,
			maxVotesPerOption: (state) => state.poll.limits.maxVotesPerOption,
			maxVotesPerUser: (state) => state.poll.limits.maxVotesPerUser,
			optionsCount: (state) => state.options.list.length,
			isLocked: (state) => state.poll.currentUserStatus.isLocked,
			userRole: (state) => state.poll.currentUserStatus.userRole,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			confirmedOptions: 'options/confirmed',
			hasShares: 'shares/hasShares',
			proposalsOpen: 'poll/proposalsOpen',
		}),

		showUnpublishedPollCard() {
			return this.pollAccess === 'private' && !this.hasShares && this.allowEdit && this.optionsCount
		},

		showAddProposalsCard() {
			return this.allowAddOptions && this.proposalsOpen && !this.closed
		},

		showClosedCard() {
			return this.closed && !this.showSendConfirmationsCard
		},

		showSendConfirmationsCard() {
			return this.allowEdit && this.closed && this.confirmedOptions.length > 0
		},

		showLimitCard() {
			return this.allowVote && !this.closed && (this.maxVotesPerOption || this.maxVotesPerUser)
		},

		showRegisterCard() {
			return (this.$route.name === 'publicVote'
				&& ['public', 'email', 'contact'].includes(this.userRole)
				&& !this.closed
				&& !this.isLocked
				&& !!this.pollId
			)
		},

	},
}
</script>
