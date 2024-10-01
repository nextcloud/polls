<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import { CardAddProposals, CardClosedPoll, CardLimitedVotes, CardLocked, CardRegister, CardSendConfirmations, CardUnpublishedPoll } from './index.js'
	import { usePollStore, AccessType } from '../../stores/poll.ts'
	import { useOptionsStore } from '../../stores/options.ts'
	import { useSharesStore } from '../../stores/shares.ts'
	import { useSessionStore } from '../../stores/session.ts'
	import { UserType } from '../../Types/index.ts'

	const pollStore = usePollStore()
	const optionsStore = useOptionsStore()
	const sharesStore = useSharesStore()
	const sessionStore = useSessionStore()

	const showUnpublishedPollCard = computed(() => pollStore.configuration.access === AccessType.Private && !sharesStore.hasShares && pollStore.permissions.edit && optionsStore.list.length)
	const showAddProposalsCard = computed(() => pollStore.permissions.addOptions && pollStore.isProposalOpen && !pollStore.isClosed)
	const showClosedCard = computed(() => pollStore.isClosed && !showSendConfirmationsCard.value)
	const showSendConfirmationsCard = computed(() => pollStore.permissions.edit && pollStore.isClosed && optionsStore.confirmed.length > 0)
	const showLimitCard = computed(() => pollStore.permissions.vote && !pollStore.isClosed && (pollStore.configuration.maxVotesPerOption || pollStore.configuration.maxVotesPerUser))
	const showRegisterCard = computed(() => (sessionStore.route.name === 'publicVote'
		&& [UserType.Public, UserType.Email, UserType.Contact].includes(pollStore.currentUserStatus.userRole)
		&& !pollStore.isClosed
		&& !pollStore.currentUserStatus.isLocked
		&& !!pollStore.id
	))

</script>

<template>
	<TransitionGroup>
		<CardUnpublishedPoll v-if="showUnpublishedPollCard" :key="0" />
		<CardAddProposals v-if="showAddProposalsCard" :key="1" />
		<CardLimitedVotes v-if="showLimitCard" :key="2" />
		<CardClosedPoll v-if="showClosedCard" :key="3" />
		<CardSendConfirmations v-if="showSendConfirmationsCard" :key="4" />
		<CardLocked v-if="pollStore.currentUserStatus.isLocked" :key="5" />
		<CardRegister v-if="showRegisterCard" :key="6" />
	</TransitionGroup>
</template>

