<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { AxiosError } from '@nextcloud/axios'

import { t } from '@nextcloud/l10n'
import { showSuccess, showError } from '@nextcloud/dialogs'

import VoteIndicator from './VoteIndicator.vue'
import { usePollStore } from '../../stores/poll.ts'
import { Answer, useVotesStore } from '../../stores/votes.ts'
import { Option, User } from '../../Types/index.ts'

interface Props {
	option: Option
	user: User
}

export type richAnswer = {
	name: Answer
	translated: string
}

const richAnswers: { [key in Answer]: richAnswer } = {
	yes: { name: 'yes', translated: t('polls', 'Yes') },
	maybe: { name: 'maybe', translated: t('polls', 'Maybe') },
	no: { name: 'no', translated: t('polls', 'No') },
	'': { name: '', translated: t('polls', 'No answer') },
}

const { option, user } = defineProps<Props>()

const pollStore = usePollStore()
const votesStore = useVotesStore()

const vote = computed(() =>
	votesStore.getVote({
		option,
		user,
	}),
)

const nextAnswer = computed<richAnswer>(() => {
	if (['no', ''].includes(vote.value.answer)) {
		return richAnswers.yes
	}

	if (vote.value.answer === 'yes' && pollStore.configuration.allowMaybe) {
		return richAnswers.maybe
	}

	return pollStore.configuration.useNo ? richAnswers.no : richAnswers['']
})

async function setVote() {
	try {
		await votesStore.set({
			option,
			setTo: nextAnswer.value.name,
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
		:class="[vote.answer]"
		:aria-label="
			t('polls', 'Click to vote with {nextAnswer} for option {option}', {
				option: option.text,
				nextAnswer: nextAnswer.translated,
			})
		"
		@click="setVote()">
		<VoteIndicator :answer="vote.answer" />
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
