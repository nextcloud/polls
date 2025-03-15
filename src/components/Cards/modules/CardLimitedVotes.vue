<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { CardDiv } from '../../Base/index.js'
import ActionDeleteOrphanedVotes from '../../Actions/modules/ActionDeleteOrphanedVotes.vue'
import { t, n } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

const pollStore = usePollStore()

const orphanedVotesText = computed(() =>
	n(
		'polls',
		'%n orphaned vote of a probaly deleted option is possibly blocking your vote limit.',
		'%n orphaned votes of probaly deleted options are possibly blocking your vote limit.',
		pollStore.currentUserStatus.orphanedVotes,
	),
)

const votesLeft = computed(() =>
	pollStore.configuration.maxVotesPerUser - pollStore.currentUserStatus.yesVotes >
	0
		? pollStore.configuration.maxVotesPerUser -
			pollStore.currentUserStatus.yesVotes
		: 0,
)

const cardType = computed(() =>
	pollStore.configuration.maxVotesPerUser && votesLeft.value < 1
		? 'error'
		: 'info',
)
</script>

<template>
	<CardDiv :heading="t('polls', 'Limited votes.')" :type="cardType">
		<ul>
			<li v-if="pollStore.configuration.maxVotesPerOption">
				{{
					n(
						'polls',
						'%n vote is allowed per option.',
						'%n votes are allowed per option.',
						pollStore.configuration.maxVotesPerOption,
					)
				}}
			</li>
			<li v-if="pollStore.configuration.maxVotesPerUser">
				{{
					n(
						'polls',
						'%n vote is allowed per participant.',
						'%n votes are allowed per participant.',
						pollStore.configuration.maxVotesPerUser,
					)
				}}
				{{
					n(
						'polls',
						'You have %n vote left.',
						'You have %n votes left.',
						votesLeft,
					)
				}}
			</li>
			<div
				v-if="
					pollStore.currentUserStatus.orphanedVotes &&
					pollStore.configuration.maxVotesPerUser
				">
				<b>{{ orphanedVotesText }}</b>
			</div>
		</ul>

		<template
			v-if="
				pollStore.currentUserStatus.orphanedVotes &&
				pollStore.configuration.maxVotesPerUser
			"
			#button>
			<ActionDeleteOrphanedVotes />
		</template>
	</CardDiv>
</template>
