<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="vote-item" :class="[answer, { active: isVotable }, {currentuser: isCurrentUser}]">
		<VoteIndicator :answer="iconAnswer"
			:active="isVotable"
			@click="setVote()" />
	</div>
</template>

<script>

import { mapStores } from 'pinia'
import { showSuccess, showError } from '@nextcloud/dialogs'
import VoteIndicator from './VoteIndicator.vue'
import { t } from '@nextcloud/l10n'
import { useAclStore } from '../../stores/acl.ts'
import { usePollStore } from '../../stores/poll.ts'
import { useVotesStore } from '../../stores/votes.ts'

export default {
	name: 'VoteItem',

	components: {
		VoteIndicator,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		userId: {
			type: String,
			default: '',
		},
	},

	computed: {
		...mapStores(usePollStore, useVotesStore, useAclStore),

		isVotable() {
			return this.isActive
				&& this.isValidUser
				&& !this.pollStore.isClosed
				&& !this.option.locked
		},

		isActive() {
			return this.isCurrentUser && this.pollStore.permissions.vote
		},

		isCurrentUser() {
			return this.aclStore.currentUser.userId === this.userId
		},

		answer() {
			return this.votesStore.getVote({
				option: this.option,
				userId: this.userId,
			}).answer
		},

		iconAnswer() {
			if (this.answer === 'no') {
				return (this.pollStore.isClosed && this.option.confirmed) || this.isActive ? 'no' : ''
			}
			if (this.answer === '') {
				return (this.pollStore.isClosed && this.option.confirmed) ? 'no' : ''
			}
			return this.answer
		},

		nextAnswer() {
			if (this.pollStore.answerSequence.indexOf(this.answer) < 0) {
				return this.pollStore.answerSequence[1]
			}
			return this.pollStore.answerSequence[(this.pollStore.answerSequence.indexOf(this.answer) + 1) % this.pollStore.answerSequence.length]

		},

		isValidUser() {
			return (this.userId !== '' && this.userId !== null)
		},

	},

	methods: {
		async setVote() {
			try {
				await this.votesStore.set({
					option: this.option,
					setTo: this.nextAnswer,
				})
				showSuccess(t('polls', 'Vote saved'), { timeout: 2000 })
			} catch (e) {
				showError(t('polls', 'Error saving vote'))

			}
		},
	},
}

</script>

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

		&.currentuser {
			background-color: transparent !important;
			.locked & {
				background-color: var(--color-polls-background-no) !important;
			}
		}
	}

	.locked {
		.vote-item.currentuser {
			background-color: var(--color-polls-background-no) !important;
		}
	}
}

</style>
