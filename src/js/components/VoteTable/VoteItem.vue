<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="vote-item" :class="[answer, { active: isVotable }, {currentuser: isCurrentUser}]">
		<VoteIndicator :answer="iconAnswer"
			:active="isVotable"
		        :disabled="!isVotable" 
			@click="setVote()" 
			@select-change="handleRankSelected" 
			/>
	</div>
</template>

<script>

import { mapGetters, mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import VoteIndicator from './VoteIndicator.vue'

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
		...mapState({
			currentUser: (state) => state.acl.currentUser,
			allowVote: (state) => state.poll.permissions.vote,
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
			answerSequence: 'poll/answerSequence',
		}),

		isVotable() {
			return this.isActive
				&& this.isValidUser
				&& !this.isPollClosed
				&& !this.option.locked
		},

		isActive() {
			return this.isCurrentUser && this.allowVote;
		},

		isCurrentUser(){
			return this.currentUser.userId === this.userId;
		},

		answer() {
			return this.$store.getters['votes/getVote']({
				option: this.option,
				userId: this.userId,
			}).answer
		},

		iconAnswer() {
			if (this.answer === 'no') {
				return (this.isPollClosed && this.option.confirmed) || this.isActive ? 'no' : ''
			}
			if (this.answer === '') {
				return (this.isPollClosed && this.option.confirmed) ? 'no' : ''
			}
			return this.answer
		},

		nextAnswer() {
			if (this.answerSequence.indexOf(this.answer) < 0) {
				return this.answerSequence[1]
			}
			return this.answerSequence[(this.answerSequence.indexOf(this.answer) + 1) % this.answerSequence.length]

		},

		isValidUser() {
			return (this.userId !== '' && this.userId !== null)
		},

	},

	methods: {

		async handleRankSelected(rank) {
			await this.setVote(rank);
  		},

		async setVote(rank) {
			try {
				if (this.pollType ==='textRankPoll') {
					const setTo = String(rank);

					await this.$store.dispatch('votes/set', {
						option: this.option,
						userId: this.userId,
						setTo,
						});
				}
				else 
				await this.$store.dispatch('votes/set', {
					option: this.option,
					userId: this.userId,
					setTo: this.nextAnswer,
				});
					
				showSuccess(t('polls', 'Vote saved'), { timeout: 2000 })
			} catch (e) {
				if (e.response.status === 409 && e.response.data.message === 'Vote limit exceeded') {
					showError(t('polls', 'Vote already booked out'))
					return
				}
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
