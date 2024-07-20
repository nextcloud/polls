<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import VoteIndicator from '../VoteTable/VoteIndicator.vue'
import { useComboStore } from '../../stores/combo.ts'
import { computed, defineProps, PropType } from 'vue'
import { Option } from '../../stores/options.ts'
import { User } from '../../Interfaces/interfaces.ts'

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
	pollId: {
		type: Number,
		default: 0,
	},
})

const answer = computed(() => comboStore.getVote({
		option: props.option,
		user: props.user,
	}).answer)

const iconAnswer = computed(() => {
	if (answer.value === 'no') {
		// TODO: check isActive
		// return (closed && props.option.confirmed) || isActive ? 'no' : ''
		return (closed && props.option.confirmed) ? 'no' : ''
	}
	if (answer.value === '') {
		return (closed && props.option.confirmed) ? 'no' : ''
	}
	return answer.value
})

const foreignOption = computed(() => !comboStore.optionBelongsToPoll({
		text: props.option.text,
		pollId: props.pollId,
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
