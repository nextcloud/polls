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
		<div class="poll-information">
			<h3>
				<UserBubble :user="poll.owner" :display-name="poll.owner" />
				{{ t('polls', ' started this poll on %n. ', 1, moment.unix(poll.created).format('LLLL')) }}
				<span v-if="expired">{{ t('polls', 'Voting is no more possible, because this poll expired since %n', 1, moment.unix(poll.expire).format('LLLL')) }}</span>
				<span v-if="!expired && poll.expire && acl.allowVote">{{ t('polls', 'You can place your vote until %n. ',1, moment.unix(poll.expire).format('LLLL')) }}</span>
				<span v-if="poll.anonymous">{{ t('polls', 'The names of other participnts is hidden, as this is a anonymous poll. ') }}</span>
			</h3>
		</div>
		<ul class="vote-table">
			<li v-for="(option) in sortedOptions" :key="option.id" class="vote-row">
				<VoteTableItem
					v-if="acl.allowVote"
					:user-id="acl.userId"
					:option="option"
					@voteClick="setVote(option, acl.userId)" />
				<TextPollItem :option="option" />

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

		<div v-if="acl.allowSeeUsernames">
			<h2>{{ t('polls','Participants of this poll so far') }}</h2>
			<div class="participants">
				<userDiv v-for="(participant) in participants"
					:key="participant"
					:hide-names="true"
					:user-id="participant"
					type="user" />
			</div>
		</div>

		<div v-else>
			<h2>{{ t('polls','Participants names are hidden, because this is an anoymous poll') }} </h2>
		</div>
		<h2>{{ t('polls','Comments') }} </h2>
		<SideBarTabComments />
	</div>
</template>

<script>
import TextPollItem from '../Base/TextPollItem'
import SideBarTabComments from '../SideBar/SideBarTabComments'
import VoteTableItem from './VoteTableItem'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteTable',
	components: {
		TextPollItem,
		SideBarTabComments,
		VoteTableItem
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl
		}),

		...mapGetters([
			'sortedOptions',
			'participants',
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
			return this.participants.length - this.maybeVotes(pollOptionText) - this.yesVotes(pollOptionText)
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
	.vote-list {
		margin: 8px 24px;
	}
	.user-row.sticky,
	.header > .sticky {
		position: sticky;
		left: 0;
		background-color: var(--color-main-background);
		width: 170px;
		flex: 0 0 auto;
	}

	.header {
		height: 150px;
	}

	.user {
		height: 44px;
		padding: 0 17px;
	}

	.poll-item {
		flex: 3;
	}

	.vote-item {
		flex: 0;
	}
	.participants {
		display: flex;
		justify-content: flex-start;
		.user-row {
			display: block;
			flex: 0;
		}
		.user {
			padding: 0;
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
	.vote-table {
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

	.vote-rowh {
		> li {
			display: flex;
			align-items: center;
			padding-left: 8px;
			padding-right: 8px;
			line-height: 2em;
			min-height: 4em;
			border-bottom: 1px solid var(--color-border);
			overflow: hidden;
			white-space: nowrap;

			> div {
				display: flex;
				flex: 1;
				font-size: 1.2em;
				opacity: 0.7;
				white-space: normal;
				padding-right: 4px;
				&.avatar {
					flex: 0;
				}
			}

			> div:nth-last-child(1) {
				justify-content: center;
				flex: 0 0;
			}
		}
	}

	@media (max-width: (480px)) {
		.vote-table {
			flex: 1 0;
			flex-direction: row;
			min-width: 300px;

			&> div {
				display: none;
				&> div {
					width: unset;
					margin: 0;

				}
			}

			&> .currentuser {
				display: flex;
				flex-direction: column;
				&> .user-row {
					display: none;
				}
			}

			&> .header, {
				height: initial;
				padding-left: initial;
				display: flex;
				flex-direction: column;
				flex: 3 1;
				justify-content: space-around;
				align-items: stretch;
				&> .vote-header {
					display: flex;
					flex-direction: row;
					&> .counter {
						align-items: baseline;
					}
				}
			}
		}
	}
</style>
