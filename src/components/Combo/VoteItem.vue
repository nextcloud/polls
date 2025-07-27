<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import VoteIndicator from '../VoteTable/VoteIndicator.vue'
import { useComboStore } from '../../stores/combo'
import { computed } from 'vue'

import type { Answer } from '../../stores/votes.types'
import type { Option } from '../../stores/options.types'
import type { Poll } from '../../stores/poll.types'
import type { User } from '../../Types'

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
	if (['no', ''].includes(answer.value)) {
		return poll.status.isExpired && option.confirmed ? 'no' : ''
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
