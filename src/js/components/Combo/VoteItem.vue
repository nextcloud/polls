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
	<div class="vote-item" :class="[answer, {empty: foreignOption}]">
		<div class="icon" />
		<slot name="indicator" />
	</div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
	name: 'VoteItem',

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		user: {
			type: Object,
			default: null,
		},
		pollId: {
			type: Number,
			default: 0,
		},
	},

	computed: {
		...mapGetters({
			optionBelongsToPoll: 'combo/optionBelongsToPoll',
		}),

		answer() {
			return this.$store.getters['combo/getVote']({
				option: this.option,
				user: this.user,
			}).voteAnswer
		},

		foreignOption() {
			return !this.optionBelongsToPoll({
				pollOptionText: this.option.pollOptionText,
				pollId: this.pollId,
			})
		},
	},
}

</script>

<style lang="scss">

.vote-item {
	display: flex;
	background-color: var(--color-polls-background-no);
	transition: background-color 1s ease-out;
	> .icon {
		color: var(--color-polls-foreground-no);
		background-position: center;
		background-repeat: no-repeat;
		background-size: 90%;
		width: 30px;
		height: 30px;
		flex: 0 0 auto;
	}

	&.empty {
		background-color: transparent;
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
			color: var(--color-polls-foreground-maybe);
			background-image: var(--icon-polls-maybe)
		}
	}

	&.active {
		background-color: transparent;
		> .icon {
			cursor: pointer;
			border: 2px solid;
			border-radius: var(--border-radius);
		}
	}
}

.vote-item.confirmed {
	background-color: transparent;
	&:not(.yes):not(.maybe) .icon {
		background-image: var(--icon-polls-no);
	}
}

</style>
