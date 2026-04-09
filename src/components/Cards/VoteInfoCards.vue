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
import CardSendConfirmations from './CardSendConfirmations.vue'
import CardTimezone from './CardTimezone.vue'
import CardUnpublishedPoll from './CardUnpublishedPoll.vue'

import { usePollStore } from '../../stores/poll'
import { useOptionsStore } from '../../stores/options'
import { useSharesStore } from '../../stores/shares'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const sharesStore = useSharesStore()

const showTimezoneHint = computed(
	() =>
		pollStore.type === 'datePoll'
		&& Intl.DateTimeFormat().resolvedOptions().timeZone
			!== pollStore.getTimezoneName,
)

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
</script>

<template>
	<TransitionGroup tag="div" class="vote-info-cards">
		<CardLimitedVotes v-if="showLimitCard" :key="2" />
		<CardTimezone v-if="showTimezoneHint" />
		<CardUnpublishedPoll v-if="showUnpublishedPollCard" :key="0" />
		<CardClosedPoll v-if="showClosedCard" :key="3" />
		<CardLocked v-if="pollStore.currentUserStatus.isLocked" :key="5" />
		<CardAddProposals v-if="showAddProposalsCard" :key="1" />
		<CardSendConfirmations v-if="showSendConfirmationsCard" :key="4" />
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
