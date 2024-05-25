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
			pollAccess: (state) => state.poll.configuration.access,
			pollId: (state) => state.poll.id,
			permissions: (state) => state.poll.permissions,
			maxVotesPerOption: (state) => state.poll.configuration.maxVotesPerOption,
			maxVotesPerUser: (state) => state.poll.configuration.maxVotesPerUser,
			optionsCount: (state) => state.options.list.length,
			isLocked: (state) => state.poll.currentUserStatus.isLocked,
			userRole: (state) => state.poll.currentUserStatus.userRole,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
			confirmedOptions: 'options/confirmed',
			hasShares: 'shares/hasShares',
			isProposalOpen: 'poll/isProposalOpen',
		}),

		showUnpublishedPollCard() {
			return this.pollAccess === 'private' && !this.hasShares && this.permissions.edit && this.optionsCount
		},

		showAddProposalsCard() {
			return this.permissions.addOptions && this.isProposalOpen && !this.isPollClosed
		},

		showClosedCard() {
			return this.isPollClosed && !this.showSendConfirmationsCard
		},

		showSendConfirmationsCard() {
			return this.permissions.edit && this.isPollClosed && this.confirmedOptions.length > 0
		},

		showLimitCard() {
			return this.permissions.vote && !this.isPollClosed && (this.maxVotesPerOption || this.maxVotesPerUser)
		},

		showRegisterCard() {
			return (this.$route.name === 'publicVote'
				&& ['public', 'email', 'contact'].includes(this.userRole)
				&& !this.isPollClosed
				&& !this.isLocked
				&& !!this.pollId
			)
		},

	},
}
</script>
