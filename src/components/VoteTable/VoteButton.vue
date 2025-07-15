<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { showSuccess, showError } from '@nextcloud/dialogs'

import { usePollStore } from '../../stores/poll.ts'
import { useVotesStore } from '../../stores/votes.ts'
import { Option, User } from '../../Types/index.ts'

import { t } from '@nextcloud/l10n'
import VoteIndicator from './VoteIndicator.vue'
import { AxiosError } from '@nextcloud/axios'

interface Props {
	option: Option
	user: User
}

const { option, user } = defineProps<Props>()

const pollStore = usePollStore()
const votesStore = useVotesStore()

const thisVote = computed(() =>
	votesStore.getVote({
		option,
		user,
	}),
)

const iconAnswer = computed(() => {
	if (['no', ''].includes(thisVote.value.answer)) {
		return pollStore.isClosed && option.confirmed ? 'no' : ''
	}
	return thisVote.value.answer
})

const nextAnswer = computed(() => {
	if (pollStore.answerSequence.indexOf(thisVote.value.answer) < 0) {
		return pollStore.answerSequence[1]
	}
	return pollStore.answerSequence[
		(pollStore.answerSequence.indexOf(thisVote.value.answer) + 1)
			% pollStore.answerSequence.length
	]
})
const nextAnswerTranslated = computed(() => {
	if (nextAnswer.value === 'yes') {
		return t('polls', 'Yes')
	}
	if (nextAnswer.value === 'maybe') {
		return t('polls', 'Maybe')
	}
	return t('polls', 'No')
})

async function setVote() {
	try {
		await votesStore.set({
			option,
			setTo: nextAnswer.value,
		})
		showSuccess(t('polls', 'Vote saved'), { timeout: 2000 })
	} catch (error) {
		if ((error as AxiosError).status === 409) {
			showError(t('polls', 'Vote already booked out'))
		} else {
			showError(t('polls', 'Error saving vote'))
		}
	}
}
</script>

<template>
	<button
		class="vote-button active"
		:class="[thisVote.answer]"
		:aria-label="
			t('polls', 'Vote {nextAnswer} for {option}', {
				option: option.text,
				nextAnswer: nextAnswerTranslated,
			})
		"
		@click="setVote()">
		<VoteIndicator :answer="iconAnswer" />
	</button>
</template>

<style lang="scss" scoped>
button.vote-button {
	flex: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.4s ease-in-out;
	background-clip: content-box;
	background-color: transparent;
	border: none;
	margin: 0;
	padding: 0;
	width: max-content;

	&:hover,
	&:focus {
		background-color: inherit;
		border: none;
		margin: 0;
		padding: 0;
	}
}
</style>
