<template lang="html">
	<div class="vote-table">
		<transition-group v-if="event.type === 'datePoll'" name="voteOptions" tag="div" class="header">
			<date-poll-vote-header v-for="(option) in sortedVoteOptions" :key="option.text" :option="option" :poll-type="event.type"
			/>
		</transition-group>

		<transition-group v-if="event.type === 'textPoll'" name="voteOptions" tag="div" class="header">
			<text-poll-vote-header v-for="(option) in sortedVoteOptions" :key="option.text" :option="option" :poll-type="event.type"
			/>
		</transition-group>
		<ul name="participants" class="participants">
			<div v-for="(participant) in poll.participants" :key="participant" :class="{currentUser: (participant === poll.currentUser) }">
				<user-div :class="{currentUser: (participant === poll.currentUser) }" :key="participant" :user-id="participant" :fixedWidth="true"
				/>
				<div class="vote-row">
					<vote-item v-for="vote in usersVotes(participant)"
					           :key="vote.id"
					           :option="vote"
					           :edit="poll.currentUser === participant"
					           :poll-type="event.type"
					           @voteClick="cycleVote(vote)" />
				</div>
			</div>
		</ul>
	</div>
</template>

<script>
	import moment from 'moment'
	import VoteItem from '../components/base/voteItem'
	import DatePollVoteHeader from '../components/datePoll/voteHeader'
	import TextPollVoteHeader from '../components/textPoll/voteHeader'
	import { mapState, mapGetters } from 'vuex'

	export default {
		name: 'VoteTable',
		components: {
			DatePollVoteHeader,
			TextPollVoteHeader,
			VoteItem,
		},

		computed: {
			...mapState({
				poll: state => state.poll,
				event: state => state.poll.event,
				participants: state => state.poll.participants,
				voteOptions: state => state.poll.voteOptions,
			}),

			...mapGetters([
				'sortedVoteOptions',
				'usersVotes',
			]),
		},

		methods: {
			cycleVote(payload) {
				var switchTo = 'yes'

				if (payload.voteAnswer === 'yes') {
					switchTo = 'no'
				} else if (payload.voteAnswer === 'no' && this.event.allowMaybe) {
					switchTo = 'maybe'
				}
				this.$store.commit('changeVote', { payload, switchTo })
			},
		},
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
