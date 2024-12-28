<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">

	import { computed } from 'vue'
	import { showSuccess, showError } from '@nextcloud/dialogs'

	import { useSessionStore } from '../../stores/session.ts'
	import { usePollStore } from '../../stores/poll.ts'
	import { useVotesStore } from '../../stores/votes.ts'
	import { Option, User } from '../../Types/index.ts'

	import { t } from '@nextcloud/l10n'
	import VoteIndicator from './VoteIndicator.vue'

	export interface Props {
		option?: Option
		user: User
	}

	const pollStore = usePollStore()
	const sessionStore = useSessionStore()
	const votesStore = useVotesStore()

	const props = withDefaults(defineProps<Props>(), {
		option: undefined,
		user: undefined,
	})

	const isVotable = computed(() => isActive.value
		&& isValidUser.value
		&& !pollStore.isClosed
		&& !props.option.locked)

	const isActive = computed(() => isCurrentUser.value && pollStore.permissions.vote)

	const isCurrentUser = computed(() => sessionStore.currentUser.id === props.user.id)

	const answer = computed(() => votesStore.getVote({
		option: props.option,
		userId: props.user.id,
	}).answer)

	const iconAnswer = computed(() => {
		if (answer.value === 'no') {
			return (pollStore.isClosed && props.option.confirmed) || isActive.value ? 'no' : ''
		}
		if (answer.value === '') {
			return (pollStore.isClosed && props.option.confirmed) ? 'no' : ''
		}
		return answer.value
	})

	const nextAnswer = computed(() => {
		if (pollStore.answerSequence.indexOf(answer.value) < 0) {
			return pollStore.answerSequence[1]
		}
		return pollStore.answerSequence[(pollStore.answerSequence.indexOf(answer.value) + 1) % pollStore.answerSequence.length]

	})

	const isValidUser = computed(() => (props.user.id !== '' && props.user.id !== null))

	/**
	 *
	 */
	async function setVote() {
		if (isVotable.value) {
			try {
				await votesStore.set({
					option: props.option,
					setTo: nextAnswer.value,
				})
				showSuccess(t('polls', 'Vote saved'), { timeout: 2000 })
			} catch (error) {
				if (error.response.status === 409 && error.response.data.message === 'Vote limit exceeded') {
					showError(t('polls', 'Vote already booked out'))
				} else {
					showError(t('polls', 'Error saving vote'))
				}
			}
		} else {
			showError(t('polls', 'Error saving vote'))
		}
	}

</script>

<template>
	<div class="vote-item" :class="[answer, { active: isVotable }, {'current-user': isCurrentUser}]">
		<VoteIndicator :answer="iconAnswer"
			:active="isVotable"
			@click="setVote()" />
	</div>
</template>

<style lang="scss" scoped>

.vote-item {
	display: flex;
	background-color: var(--color-polls-background-no);
	transition: all 0.4s ease-in-out;

	&.yes {
		background-color: var(--color-polls-background-yes);
	}

	&.maybe {
		background-color: var(--color-polls-background-maybe);
	}

	&.active, &.active.no {
		background-color: transparent;
	}

	&.no {
		background-color: var(--color-polls-background-no);
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

		&.current-user {
			background-color: transparent !important;
			.locked & {
				background-color: var(--color-polls-background-no) !important;
			}
		}
	}

	.locked {
		.vote-item.current-user {
			background-color: var(--color-polls-background-no) !important;
		}
	}
}

</style>
