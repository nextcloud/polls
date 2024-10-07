<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import VoteIndicator from '../VoteTable/VoteIndicator.vue'
	import { useComboStore } from '../../stores/combo.ts'
	import { computed, PropType } from 'vue'
	import { Option, Poll, Answer, User } from '../../Types/index.ts'

	const comboStore = useComboStore()

	const props = defineProps({
		option: {
			type: Object as PropType<Option>,
			default: undefined,
		},
		user: {
			type: Object as PropType<User>,
			default: null,
		},
		poll: {
			type: Object as PropType<Poll>,
			default: null,
		},
	})

	const answer = computed(() => comboStore.getVote({
		userId: props.user.id,
		optionText: props.option.text,
		pollId: props.poll.id,
	}).answer)

	const iconAnswer = computed(() => {
		if (answer.value === Answer.No) {
			// TODO: check isActive
			// return (closed && props.option.confirmed) || isActive ? 'no' : ''
			return (props.poll.status.expired && props.option.confirmed) ? Answer.No : Answer.None
		}
		if (answer.value === '') {
			return (props.poll.status.expired && props.option.confirmed) ? Answer.No : Answer.None
		}
		return answer.value
	})

	const foreignOption = computed(() => !comboStore.optionBelongsToPoll({
		text: props.option.text,
		pollId: props.poll.id,
	}))

</script>

<template>
	<div class="vote-item" :class="[answer, {empty: foreignOption}]">
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
