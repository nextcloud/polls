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
		<VoteIndicator :answer="iconAnswer" />
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import VoteIndicator from '../VoteTable/VoteIndicator.vue'

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

		foreignOption() {
			return !this.optionBelongsToPoll({
				text: this.option.text,
				pollId: this.pollId,
			})
		},
	},
}

</script>

<style lang="scss">

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
