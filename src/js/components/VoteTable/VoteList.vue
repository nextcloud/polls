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
	<ul class="vote-list">
		<li v-for="(option) in sortedOptions" :key="option.id" class="vote-row">
			<VoteTableItem
				v-if="acl.allowVote"
				:user-id="acl.userId"
				:option="option"
				@voteClick="setVote(option, acl.userId)" />
			<PollItemText v-if="poll.type === 'textPoll'" :option="option" />
			<PollItemDate v-if="poll.type === 'datePoll'" :option="option" />

			<div class="counter">
				<div v-if="yesVotes(option.pollOptionText)" class="yes" :style="{ flex: yesVotes(option.pollOptionText) }">
					<span> {{ yesVotes(option.pollOptionText) }} </span>
				</div>

				<div v-if="maybeVotes(option.pollOptionText)" class="maybe" :style="{flex: maybeVotes(option.pollOptionText) }">
					<span> {{ maybeVotes(option.pollOptionText) }} </span>
				</div>

				<div v-if="noVotes(option.pollOptionText)" class="no" :style="{flex: noVotes(option.pollOptionText) }">
					<span> {{ noVotes(option.pollOptionText) }} </span>
				</div>
			</div>
		</li>
	</ul>
</template>

<script>
import PollItemText from '../Base/PollItemText'
import PollItemDate from '../Base/PollItemDate'
import VoteTableItem from './VoteTableItem'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteTable',
	components: {
		PollItemDate,
		PollItemText,
		VoteTableItem
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl
		}),

		...mapGetters([
			'sortedOptions',
			'participantsVoted',
			'votesRank',
			'expired'
		]),

		currentUser() {
			return this.acl.userId
		},

		noOptions() {
			return (this.sortedOptions.length === 0)
		}

	},

	methods: {

		yesVotes(pollOptionText) {
			return this.votesRank.find(rank => {
				return rank.pollOptionText === pollOptionText
			}).yes
		},

		maybeVotes(pollOptionText) {
			return this.votesRank.find(rank => {
				return rank.pollOptionText === pollOptionText
			}).maybe
		},

		noVotes(pollOptionText) {
			return this.participantsVoted.length - this.maybeVotes(pollOptionText) - this.yesVotes(pollOptionText)
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

	.vote-list {
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
