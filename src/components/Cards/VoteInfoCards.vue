<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import CardAddProposals from './CardAddProposals.vue'
import CardClosedPoll from './CardClosedPoll.vue'
import CardLimitedVotes from './CardLimitedVotes.vue'
import CardLocked from './CardLocked.vue'
import CardRegister from './CardRegister.vue'
import CardSendConfirmations from './CardSendConfirmations.vue'
import CardUnpublishedPoll from './CardUnpublishedPoll.vue'

import { usePollStore } from '../../stores/poll'
import { useOptionsStore } from '../../stores/options'
import { useSharesStore } from '../../stores/shares'
import { useSessionStore } from '../../stores/session'

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
	display: grid;
	gap: 1rem;
	grid-template-columns: repeat(auto-fit, minmax(calc(var(--cap-width) / 2), 1fr));

	// remove margin from notecard in favor of gap
	.notecard {
		margin: unset;
	}
}
</style>
