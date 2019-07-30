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
	<div class="vote-table">
		<transition-group v-if="event.type === 'datePoll'" name="voteOptions" tag="div"
			class="header"
		>
			<date-poll-vote-header v-for="(option) in sortedOptions"
				:key="option.text"
				:option="option"
				:poll-type="event.type"
				:mode="poll.mode"
				@remove="optionRemove(option)"
			/>
		</transition-group>

		<transition-group v-if="event.type === 'textPoll'" name="voteOptions" tag="div"
			class="header"
		>
			<text-poll-vote-header v-for="(option) in sortedOptions"
				:key="option.text"
				:option="option"
				:poll-type="event.type"
			/>
		</transition-group>

		<ul class="participants">

			<div v-if="(!currentUserParticipated && poll.mode === 'vote' && !event.expired)">
				<div class="user-row fixedWidth user">
					<button class="button btn primary" @click="addMe()">
						<span>{{ t('polls', 'Add me') }}</span>
						<span v-if="writingPoll" class="icon-loading-small" />
					</button>
				</div>
				<div class="vote-row">
					No votes until now
				</div>
			</div>

			<div v-for="(participant) in participants" :key="participant" :class="{currentUser: (participant === poll.currentUser) }">
				<user-div :key="participant"
					:class="{currentUser: (participant === poll.currentUser) }"
					:user-id="participant"
					:fixed-width="true"
				/>
				<div class="vote-row">
					<vote-item v-for="vote in usersVotes(participant)"
						:key="vote.id"
						:option="vote"
						:edit="poll.currentUser === participant"
						:poll-type="event.type"
						@voteClick="cycleVote(vote)"
					/>
				</div>
			</div>
		</ul>
	</div>
</template>

<script>
import VoteItem from '../components/base/voteItem'
import DatePollVoteHeader from '../components/datePoll/voteHeader'
import TextPollVoteHeader from '../components/textPoll/voteHeader'
import { mapState, mapGetters, mapActions, mapMutations } from 'vuex'

export default {
	name: 'VoteTable',
	components: {
		DatePollVoteHeader,
		TextPollVoteHeader,
		VoteItem
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			event: state => state.event,
			voteOptions: state => state.options
		}),

		...mapGetters([
			'sortedOptions',
			'currentUserParticipated',
			'usersVotes',
			'participants'
		])
	},

	methods: {
		...mapActions([
			'addMe',
		]),

		...mapMutations([
			'optionRemove'
		]),

		// optionRemove(payload) {
		// 	this.$store.commit('optionRemove', payload)
		// },

		cycleVote(payload) {
			var switchTo = 'yes'

			if (payload.voteAnswer === 'yes') {
				switchTo = 'no'
			} else if (payload.voteAnswer === 'no' && this.event.allowMaybe) {
				switchTo = 'maybe'
			}
			this.$store.commit('voteChange', { payload, switchTo })
		}
	}
}
</script>

<style lang="scss" scoped>
	* {
		display: flex;
	}

	.vote-table {
		flex: 0;
		flex-direction: column;
		justify-content: flex-start;

		.participants {
			flex-direction: column;
			flex: 1 0;

			& > div {
				flex: 1;
				order: 2;
				border-bottom: 1px solid var(--color-border-dark);
				height: 44px;
				padding: 0 17px;
				&.currentUser {
					order: 1;
				}
			}
		}

		.header {
			height: 150px;
			padding-left: 187px;
			padding-right: 17px;
			align-items: center;
			border-bottom: 1px solid var(--color-border-dark);
			& > div {
				flex: 1;
			}
		}

		.vote-row {
			justify-content: space-between;
			flex: 1 1 auto;
		}
	}
</style>
