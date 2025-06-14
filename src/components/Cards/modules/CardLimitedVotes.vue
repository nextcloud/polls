<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { CardDiv } from '../../Base/index.ts'
import ActionDeleteOrphanedVotes from '../../Actions/modules/ActionDeleteOrphanedVotes.vue'
import { t, n } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'
import { useOptionsStore } from '../../../stores/options.ts'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()

const orphanedVotesText = computed(() =>
	n(
		'polls',
		'%n orphaned vote reduces your vote quota.',
		'%n orphaned votes reduces your vote quota.',
		pollStore.currentUserStatus.orphanedVotes,
	),
)

const votesLeft = computed(() =>
	pollStore.configuration.maxVotesPerUser - pollStore.currentUserStatus.yesVotes
	> 0
		? pollStore.configuration.maxVotesPerUser
			- pollStore.currentUserStatus.yesVotes
		: 0,
)

const optionsAvailableText = computed(() => {
	if (optionsStore.countOptionsLeft === 0) {
		return t('polls', 'No more voting options are available.')
	}

	return n(
		'polls',
		'%n voting option is available.',
		'%n voting options are available.',
		optionsStore.countOptionsLeft,
	)
})

const votesLeftText = computed(() => {
	if (!votesLeft.value) {
		return t('polls', 'You have no votes left.')
	}
	return n(
		'polls',
		'You have %n vote left out of {maxVotes}.',
		'You have %n votes left out of {maxVotes}.',
		votesLeft.value,
		{
			maxVotes: pollStore.configuration.maxVotesPerUser,
		},
	)
})

const cardType = computed(() =>
	pollStore.configuration.maxVotesPerUser && votesLeft.value < 1
		? 'error'
		: 'info',
)
</script>

<template>
	<CardDiv :heading="t('polls', 'Limited votes.')" :type="cardType">
		<span v-if="pollStore.configuration.maxVotesPerOption">
			{{ optionsAvailableText }}
		</span>
		<span v-if="pollStore.configuration.maxVotesPerUser">
			{{ votesLeftText }}
		</span>
		<div
			v-if="
				pollStore.currentUserStatus.orphanedVotes
				&& pollStore.configuration.maxVotesPerUser
			">
			<b>{{ orphanedVotesText }}</b>
		</div>

		<template
			v-if="
				pollStore.currentUserStatus.orphanedVotes
				&& pollStore.configuration.maxVotesPerUser
			"
			#button>
			<ActionDeleteOrphanedVotes />
		</template>
	</CardDiv>
</template>

<style lang="scss" scoped>
span::after {
	content: ' ';
}
</style>
