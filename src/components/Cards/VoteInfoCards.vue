<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
