<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import VoteIndicator from '../VoteTable/VoteIndicator.vue'
import { useComboStore } from '../../stores/combo.ts'
import { computed } from 'vue'
import { Option, Poll, Answer, User } from '../../Types/index.ts'

interface Props {
	option: Option
	user: User
	poll: Poll
}

const { option, user, poll } = defineProps<Props>()

const comboStore = useComboStore()

const answer = computed(
	() =>
		comboStore.getVote({
			userId: user.id,
			optionText: option.text,
			pollId: poll.id,
		}).answer as Answer,
)

const iconAnswer = computed(() => {
	if (answer.value === Answer.No) {
		return poll.status.isExpired && option.confirmed ? Answer.No : Answer.None
	}
	if (answer.value === Answer.None) {
		return poll.status.isExpired && option.confirmed ? Answer.No : Answer.None
	}
	return answer.value
})

const foreignOption = computed(
	() =>
		!comboStore.optionBelongsToPoll({
			text: option.text,
			pollId: poll.id,
		}),
)
</script>

<template>
	<div class="vote-item" :class="[answer, { empty: foreignOption }]">
		<VoteIndicator :answer="iconAnswer" />
	</div>
</template>

<style lang="scss" scoped>
.vote-item {
	display: flex;
	justify-content: center;
	align-items: center;
	background-color: var(--color-polls-background-no);

	&.empty {
		background-color: transparent;
	}

	&.yes {
		background-color: var(--color-polls-background-yes);
	}

	&.maybe {
		background-color: var(--color-polls-background-maybe);
	}

	&.no {
		background-color: var(--color-polls-background-no);
	}
}
</style>
