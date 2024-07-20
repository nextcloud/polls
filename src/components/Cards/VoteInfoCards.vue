<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { CardAddProposals, CardClosedPoll, CardLimitedVotes, CardLocked, CardRegister, CardSendConfirmations, CardUnpublishedPoll } from './index.js'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { useSharesStore } from '../../stores/shares.ts'
import { useSessionStore } from '../../stores/session.ts'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const sharesStore = useSharesStore()
const sessionStore = useSessionStore()

const showUnpublishedPollCard = computed(() => pollStore.configuration.access === 'private' && !sharesStore.hasShares && pollStore.permissions.edit && optionsStore.list.length)

const showAddProposalsCard = computed(() => pollStore.permissions.addOptions && pollStore.isProposalOpen && !pollStore.isClosed)

const showClosedCard = computed(() => pollStore.isClosed && !showSendConfirmationsCard.value)

const showSendConfirmationsCard = computed(() => pollStore.permissions.edit && pollStore.isClosed && optionsStore.confirmed.length > 0)

const showLimitCard = computed(() => pollStore.permissions.vote && !pollStore.isClosed && (pollStore.configuration.maxVotesPerOption || pollStore.configuration.maxVotesPerUser))

const showRegisterCard = computed(() => (sessionStore.route.name === 'publicVote'
		&& ['public', 'email', 'contact'].includes(pollStore.currentUserStatus.userRole)
		&& !pollStore.isClosed
		&& !pollStore.currentUserStatus.isLocked
		&& !!pollStore.id
	))

</script>

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

