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
		<CardLocked v-if="pollStore.currentUserStatus.isLocked" />
		<CardRegister v-if="showRegisterCard" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { CardAddProposals, CardClosedPoll, CardLimitedVotes, CardLocked, CardRegister, CardSendConfirmations, CardUnpublishedPoll } from './index.js'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { useSharesStore } from '../../stores/shares.ts'

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
		...mapStores(usePollStore, useOptionsStore, useSharesStore),

		showUnpublishedPollCard() {
			return this.pollStore.configuration.access === 'private' && !this.sharesStore.hasShares && this.pollStore.permissions.edit && this.optionsStore.list.length
		},

		showAddProposalsCard() {
			return this.pollStore.permissions.addOptions && this.pollStore.isProposalOpen && !this.pollStore.isPollClosed
		},

		showClosedCard() {
			return this.pollStore.isPollClosed && !this.showSendConfirmationsCard
		},

		showSendConfirmationsCard() {
			return this.pollStore.permissions.edit && this.pollStore.isPollClosed && this.pollStore.confirmedOptions.length > 0
		},

		showLimitCard() {
			return this.pollStore.permissions.vote && !this.pollStore.isPollClosed && (this.pollStore.configuration.maxVotesPerOption || this.pollStore.configuration.maxVotesPerUser)
		},

		showRegisterCard() {
			return (this.$route.name === 'publicVote'
				&& ['public', 'email', 'contact'].includes(this.pollStore.currentUserStatus.userRole)
				&& !this.pollStore.isPollClosed
				&& !this.pollStore.currentUserStatus.isLocked
				&& !!this.pollStore.id
			)
		},

	},
}
</script>
