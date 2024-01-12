<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="vote-item" :class="[answer, { active: isVotable }, {currentuser: isCurrentUser}]">
		<VoteIndicator :answer="iconAnswer"
			:active="isVotable"
			@click="setVote()" />
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
			currentUser: (state) => state.poll.acl.currentUser,
			allowVote: (state) => state.poll.acl.permissions.vote,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			answerSequence: 'poll/answerSequence',
		}),

		isVotable() {
			return this.isActive
				&& this.isValidUser
				&& !this.closed
				&& !this.option.locked
		},

		isActive() {
			return this.isCurrentUser && this.allowVote
		},

		isCurrentUser() {
			return this.currentUser.userId === this.userId
		},

		answer() {
			return this.$store.getters['votes/getVote']({
				option: this.option,
				userId: this.userId,
			}).answer
		},

		iconAnswer() {
			if (this.answer === 'no') {
				return (this.closed && this.option.confirmed) || this.isActive ? 'no' : ''
			}
			if (this.answer === '') {
				return (this.closed && this.option.confirmed) ? 'no' : ''
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
		async setVote() {
			try {
				await this.$store.dispatch('votes/set', {
					option: this.option,
					userId: this.userId,
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

<style lang="scss">

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
