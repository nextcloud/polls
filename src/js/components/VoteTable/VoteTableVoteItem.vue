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
	<div class="vote-table-vote-item" :class="[answer, { active: isActive && isValidUser &&!closed }]">
		<div v-if="isActive" class="icon" @click="setVote()" />
		<div v-else class="icon" />
	</div>
</template>

<script>

import { mapGetters } from 'vuex'
export default {
	name: 'VoteTableVoteItem',

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		userId: {
			type: String,
			default: '',
		},
		isActive: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapGetters({
			closed: 'poll/closed',
			answerSequence: 'poll/answerSequence',
		}),

		answer() {
			try {
				return this.$store.getters['poll/votes/getVote']({
					option: this.option,
					userId: this.userId,
				}).voteAnswer
			} catch (e) {
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
.vote-table-vote-item {
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

.vote-table-vote-item.confirmed:not(.yes):not(.maybe) .icon {
	background-image: var(--icon-polls-no);
}
</style>
