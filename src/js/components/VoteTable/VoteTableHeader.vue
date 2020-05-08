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
	<div class="vote-table-header" :class=" { winner: isWinner }">
		<OptionItem :option="option" :type="poll.type" :display="tableMode ? 'dateBox' : 'textBox'" />

		<div class="counter">
			<div class="yes">
				<span>{{ yesVotes }}</span>
			</div>
			<div v-if="poll.allowMaybe" class="maybe">
				<span>{{ maybeVotes }}</span>
			</div>
		</div>

		<div class="counter2">
			<div class="no" :style="{flex: noVotes }">
				<span />
			</div>

			<div v-if="maybeVotes && poll.allowMaybe" class="maybe" :style="{flex: maybeVotes }">
				<span> {{ maybeVotes }} </span>
			</div>

			<div v-if="yesVotes" class="yes" :style="{ flex: yesVotes }">
				<span> {{ yesVotes }} </span>
			</div>
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionItem from '../Base/OptionItem'

export default {
	name: 'VoteTableHeader',

	components: {
		OptionItem,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		pollType: {
			type: String,
			default: undefined,
		},
		tableMode: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			votes: state => state.votes.votes,
		}),
		...mapGetters([
			'votesRank',
			'winnerCombo',
			'participantsVoted',
		]),

		yesVotes() {
			const pollOptionText = this.option.pollOptionText
			return this.votesRank.find(rank => {
				return rank.pollOptionText === pollOptionText
			}).yes
		},

		maybeVotes() {
			const pollOptionText = this.option.pollOptionText
			return this.votesRank.find(rank => {
				return rank.pollOptionText === pollOptionText
			}).maybe
		},

		noVotes() {
			return this.participantsVoted.length - this.yesVotes - this.maybeVotes
		},

		isWinner() {
			const pollOptionText = this.option.pollOptionText
			return (
				this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).yes === this.winnerCombo.yes

				&& (this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).yes + this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).maybe > 0)

				&& this.winnerCombo.maybe === this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).maybe
			)
		},
	},
}

</script>

<style lang="scss">

.vote-table-header {
	display: flex;
	flex-direction: column;
	background-color: var(--color-main-background);
	&.winner {
		font-weight: bold;
		color: var(--color-polls-foreground-yes);
	}
}

.counter {
	display: flex;
	justify-content: center;
	font-size: 1.1em;
	padding: 14px 4px;

	&> * {
		background-position: 0px 2px;
		padding-left: 23px;
		background-repeat: no-repeat;
		background-size: contain;
		margin-right: 8px;
	}

	.yes {
		color: var(--color-polls-foreground-yes);
		background-image: var(--icon-polls-yes);
	}
	.no {
		color: var(--color-polls-foreground-no);
		background-image: var(--icon-polls-no);
	}
	.maybe {
		color: var(--color-polls-foreground-maybe);
		background-image: var(--icon-polls-maybe);
	}
}

.counter2 {
	display: flex;
	width: 80px;
	flex: 1;
	align-self: center;

	> * {
		text-align: center;
		border-radius: 21px;
		margin: 2px;
	}

	.yes {
		background-color: var(--color-polls-foreground-yes);
	}

	.maybe {
		background-color: var(--color-polls-foreground-maybe);
	}

}

@media (max-width: (480px) ) {
}

</style>
