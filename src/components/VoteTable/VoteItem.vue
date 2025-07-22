<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

import { usePollStore } from '../../stores/poll.ts'
import { useVotesStore } from '../../stores/votes.ts'
import { Option, User } from '../../Types/index.ts'

import VoteIndicator from './VoteIndicator.vue'

interface Props {
	option: Option
	user: User
	currentUser?: boolean
}

const { option, user, currentUser = false } = defineProps<Props>()

const pollStore = usePollStore()
const votesStore = useVotesStore()
const vote = computed(() =>
	votesStore.getVote({
		option,
		user,
	}),
)

const iconAnswer = computed(() => {
	if (option.locked && currentUser && !pollStore.isClosed) {
		return 'locked'
	}

	if (['', 'no'].includes(vote.value.answer)) {
		return pollStore.isClosed && (option.confirmed || currentUser) ? 'no' : ''
	}

	return vote.value.answer
})
</script>

<template>
	<div class="vote-item" :class="vote.answer">
		<VoteIndicator :answer="iconAnswer" />
	</div>
</template>

<style lang="scss" scoped>
.vote-item {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	background-color: var(--color-polls-background-no);
	transition: all 0.4s ease-in-out;
	background-clip: content-box;
	border-radius: 12px;

	&.yes {
		background-color: var(--color-polls-background-yes);
	}

	&.maybe {
		background-color: var(--color-polls-background-maybe);
	}

	.confirmed & {
		background-color: transparent;
	}
}

.vote-style-beta-510 {
	.vote-item {
		background-color: transparent;
		&.no {
			background-color: transparent;
		}
		&.yes {
			background-color: var(--color-polls-background-yes);
		}
		&.maybe {
			background-color: var(--color-polls-background-maybe);
		}
	}
}

.list-view .locked .vote-item.current-user {
	background-color: revert;
}
</style>
