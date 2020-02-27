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

<template lang="html">
	<div class="vote-list">
		<ButtonDiv :title="ranked ? t('polls', 'Original order') : t('polls', 'Order by current rank')" @click="ranked = !ranked" />
		<ul>
			<li v-for="(option) in rankedList" :key="option.id" class="vote-row">
				<div v-if="expired" class="rank" :style="style(option)">
					<span> {{ option.rank }}. </span>
				</div>
				<VoteTableItem
					v-if="acl.allowVote && !expired"
					:user-id="acl.userId"
					:option="option"
					@voteClick="setVote(option, acl.userId)" />
				<PollItemText v-if="poll.type === 'textPoll'" :option="option" />
				<PollItemDate v-if="poll.type === 'datePoll'" :option="option" />

				<div class="counter">
					<div v-if="option.yes" class="yes" :style="{ flex: option.yes }">
						<span> {{ option.yes }} </span>
					</div>

					<div v-if="option.maybe" class="maybe" :style="{flex: option.maybe }">
						<span> {{ option.maybe }} </span>
					</div>

					<div v-if="noCalculated(option)" class="no" :style="{flex: noCalculated(option) }">
						<span> {{ noCalculated(option) }} </span>
					</div>
				</div>
			</li>
		</ul>
	</div>
</template>

<script>
import ButtonDiv from '../Base/ButtonDiv'
import PollItemText from '../Base/PollItemText'
import PollItemDate from '../Base/PollItemDate'
import VoteTableItem from './VoteTableItem'
import orderBy from 'lodash/orderBy'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteList',

	components: {
		ButtonDiv,
		PollItemDate,
		PollItemText,
		VoteTableItem
	},

	data: () => ({
		ranked: false
	}),

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl
		}),

		...mapGetters([
			'sortedOptions',
			'participantsVoted',
			'expired'
		]),

		noOptions() {
			return this.sortedOptions.length === 0
		},

		rankedList() {
			return orderBy(this.sortedOptions, this.ranked ? 'rank' : 'order', 'asc')
		},

		highestRank() {
			return Math.max.apply(Math, this.sortedOptions.map(function(option) {
				return option.rank
			}))
		}
	},

	watch: {
		'poll.id': function(newValue) {
			this.ranked = this.expired
		}
	},

	mounted() {
		this.ranked = this.expired
	},

	methods: {
		noCalculated(option) {
			return this.participantsVoted.length - option.yes - option.maybe
		},

		style(option) {
			const count = this.highestRank
			const hue = 126 * (count - option.rank) / (count - 1)
			let style = 'background-color: hsl(' + hue + ', 91%, 92%);'
			if (option.rank === 1) {
				style = style.concat('color: #49bc49;font-weight: bold;')
			}
			return style
		},

		setVote(option, participant) {
			this.$store
				.dispatch('setVoteAsync', {
					option: option,
					userId: participant,
					setTo: this.$store.getters.getNextAnswer({
						option: option,
						userId: participant
					})
				})
				.then(() => {
					// this.$emit('voteSaved')
				})
		}
	}
}
</script>

<style lang="scss" scoped>

	.poll-item {
		flex: 3;
	}

	.vote-item {
		flex: 0;
	}

	.rank {
		display: flex;
		font-size: 1.2em;
		align-self: stretch;
		padding: 0 8px 0 4px;
		> * {
			text-align: right;
			margin: 2px;
			align-self: center;
			width: 25px;
		}
	}

	.counter {
		display: flex;
		width: 80px;
		flex: 1;

		> * {
			text-align: center;
			border-radius: 21px;
			margin: 2px;
		}

		.yes {
			background-color: #ebf5d6;
		}

		.maybe {
			background-color: #f0db98;
		}

		.no {
			background-color: #f45573;
		}
	}

	.vote-list ul {
		margin: 44px 0;
		display: flex;
		flex: 0;
		flex-direction: column;
		justify-content: flex-start;
		overflow: scroll;

		.vote-row {
			display: flex;
			justify-content: space-around;
			flex: 1;
			align-items: center;
			border-bottom: 1px solid var(--color-border);

			&:first-child .rank{
				border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
			}

			&:last-child .rank{
				border-radius: 0 0 var(--border-radius-large) var(--border-radius-large);
			}

			&:active,
			&:hover {
				transition: var(--background-dark) 0.3s ease;
				background-color: var(--color-background-dark); //$hover-color;
			}

			.vote-table-item {
				flex: 0;
			}

			> li {
				display: flex;
				align-items: center;
				padding-left: 8px;
				padding-right: 8px;
				line-height: 2em;
				min-height: 4em;
				overflow: hidden;
			}
		}
	}
</style>
