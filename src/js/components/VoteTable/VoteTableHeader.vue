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
		<OptionItem :option="option" :type="poll.type" display="dateBox" />

		<div class="counter">
			<div class="yes">
				<span> {{ yesVotes }} </span>
			</div>
			<div v-if="poll.allowMaybe" class="maybe">
				<span> {{ maybeVotes }} </span>
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
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			votes: state => state.votes.votes,
		}),
		...mapGetters([
			'votesRank',
			'winnerCombo',
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

<style lang="scss" scoped>

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

.text-box {
	flex: 1;
	align-self: center;
	font-size: 1.2em;
	padding-top: 14px;
	hyphens: auto;
}

.date-box {
	padding: 0 2px;
	align-items: center;
	justify-content: center;
	text-align: center;

	.month, .dow {
		font-size: 1.1em;
		color: var(--color-text-lighter);
	}
	.day {
		font-size: 1.4em;
		margin: 5px 0 5px 0;
	}
}

@media (max-width: (480px) ) {
	.vote-table-header {
		padding: 4px 0;
		display: flex;
		flex-direction: row;
		justify-content: space-around;
		border-top: 1px solid var(--color-border-dark);

		.date-box {
			padding: 0 20px 0 4px;
			align-content: center;
		}
		.counter {
			flex-direction: column;
			align-items: baseline;
			& > * {
				margin: 4px 1px;
			}
		}
	}
}

</style>
