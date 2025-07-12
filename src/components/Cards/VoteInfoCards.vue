<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import {
	CardAddProposals,
	CardClosedPoll,
	CardLimitedVotes,
	CardLocked,
	CardRegister,
	CardSendConfirmations,
	CardUnpublishedPoll,
} from './index.ts'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { useSharesStore } from '../../stores/shares.ts'
import { useSessionStore } from '../../stores/session.ts'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const sharesStore = useSharesStore()
const sessionStore = useSessionStore()

const showUnpublishedPollCard = computed(
	() =>
		pollStore.configuration.access === 'private'
		&& !sharesStore.hasShares
		&& pollStore.permissions.edit
		&& optionsStore.options.length,
)
const showAddProposalsCard = computed(
	() =>
		pollStore.permissions.addOptions
		&& pollStore.isProposalOpen
		&& !pollStore.isClosed,
)
const showClosedCard = computed(
	() => pollStore.isClosed && !showSendConfirmationsCard.value,
)
const showSendConfirmationsCard = computed(
	() =>
		pollStore.permissions.edit
		&& pollStore.isClosed
		&& optionsStore.confirmed.length > 0,
)
const showLimitCard = computed(
	() =>
		pollStore.permissions.vote
		&& !pollStore.isClosed
		&& (pollStore.configuration.maxVotesPerOption
			|| pollStore.configuration.maxVotesPerUser),
)
const showRegisterCard = computed(
	() =>
		sessionStore.route.name === 'publicVote'
		&& ['public', 'email', 'contact'].includes(
			pollStore.currentUserStatus.userRole,
		)
		&& !pollStore.isClosed
		&& !pollStore.currentUserStatus.isLocked
		&& !!pollStore.id,
)
</script>

<template>
	<TransitionGroup tag="div" class="vote-info-cards">
		<CardLimitedVotes v-if="showLimitCard" :key="2" />
		<CardUnpublishedPoll v-if="showUnpublishedPollCard" :key="0" />
		<CardClosedPoll v-if="showClosedCard" :key="3" />
		<CardLocked v-if="pollStore.currentUserStatus.isLocked" :key="5" />
		<CardAddProposals v-if="showAddProposalsCard" :key="1" />
		<CardSendConfirmations v-if="showSendConfirmationsCard" :key="4" />
		<CardRegister v-if="showRegisterCard" :key="6" />
	</TransitionGroup>
</template>

<style lang="scss" scoped>
.vote-info-cards {
	// margin: auto;
	display: flex;
	gap: 1rem;
	flex-wrap: wrap;
	justify-content: center;

	& > * {
		flex: 1;
	}

	// remove margin from notecard in favor of flexbox gap
	.notecard {
		margin: unset;
		flex: 1 calc(var(--cap-width) / 2);
		max-width: var(--cap-width);
	}
}
</style>
