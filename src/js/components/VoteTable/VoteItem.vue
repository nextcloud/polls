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
	<div class="vote-item" :class="[answer, isConfirmed, { active: isVotable }, {currentUser: isCurrentUser}]">
		<div v-if="isActive && !isLocked" class="icon" @click="setVote()" />
		<div v-else class="icon" />
		<slot name="indicator" />
	</div>
</template>

<script>

import { mapGetters, mapState } from 'vuex'
export default {
	name: 'VoteItem',

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
			voteLimit: state => state.poll.voteLimit,
			optionLimit: state => state.poll.optionLimit,
			currentUser: state => state.poll.acl.userId,
			allowVote: state => state.poll.acl.allowVote,
		}),

		...mapGetters({
			countYesVotes: 'poll/votes/countYesVotes',
			pollIsClosed: 'poll/closed',
			answerSequence: 'poll/answerSequence',
		}),

		isVotable() {
			return this.isActive && this.isValidUser && !this.pollIsClosed && !this.isLocked && !this.isBlocked
		},

		isActive() {
			return this.isCurrentUser && this.allowVote
		},

		isCurrentUser() {
			return this.currentUser === this.userId
		},

		answer() {
			return this.$store.getters['poll/votes/getVote']({
				option: this.option,
				userId: this.userId,
			}).voteAnswer
		},

		isBlocked() {
			return this.optionLimit > 0 && this.optionLimit <= this.option.yes && this.answer !== 'yes'
		},

		isLocked() {
			return (this.countYesVotes >= this.voteLimit && this.voteLimit > 0 && this.answer !== 'yes')
		},

		isConfirmed() {
			if (this.option.confirmed && this.pollIsClosed) {
				return 'confirmed'
			} else {
				return ''
			}
		},

		nextAnswer() {
			if (this.answerSequence.indexOf(this.answer) < 0) {
				return this.answerSequence[1]
			} else {
				return this.answerSequence[(this.answerSequence.indexOf(this.answer) + 1) % this.answerSequence.length]
			}
		},

		isValidUser() {
			return (this.userId !== '' && this.userId !== null)
		},

	},

	methods: {
		getEvents() {
			this.$store
				.dispatch('poll/options/getEvents', { option: this.option })
		},

		setVote() {
			this.$store
				.dispatch('poll/votes/set', {
					option: this.option,
					userId: this.userId,
					setTo: this.nextAnswer,
				})
		},
	},
}

</script>

<style lang="scss">

.vote-item {
	display: flex;
	flex: 1;
	align-items: center;
	justify-content: center;
	background-color: var(--color-polls-background-no);
	> .icon {
		color: var(--color-polls-foreground-no);
		background-position: center;
		background-repeat: no-repeat;
		background-size: 90%;
		width: 30px;
		height: 30px;
		flex: 0 0 auto;
	}

	&.yes {
		background-color: var(--color-polls-background-yes);
		> .icon {
			color: var(--color-polls-foreground-yes);
			background-image: var(--icon-polls-yes)
		}
	}

	&.no {
		background-color: var(--color-polls-background-no);
		&.active > .icon {
			color: var(--color-polls-foreground-no);
			background-image: var(--icon-polls-no)
		}
	}

	&.maybe {
		background-color: var(--color-polls-background-maybe);
		> .icon {
			background-size: 80%;
			color: var(--color-polls-foreground-maybe);
			background-image: var(--icon-polls-maybe)
		}
	}

	&.active {
		background-color: var(--color-main-background);
		> .icon {
			cursor: pointer;
			border: 2px solid;
			border-radius: var(--border-radius);
		}
	}
}

.theme--dark .vote-item {
	background-color: var(--color-polls-background-no--dark);
	&.yes {
		background-color: var(--color-polls-background-yes--dark);
	}
	&.no {
		background-color: var(--color-polls-background-no--dark);
	}
	&.maybe {
		background-color: var(--color-polls-background-maybe--dark);
	}
	&.active {
		background-color: var(--color-main-background);
	}
}

.vote-item.confirmed:not(.yes):not(.maybe) .icon {
	background-image: var(--icon-polls-no);
}

.vote-item.confirmed {
	background-color: transparent;
}

</style>
