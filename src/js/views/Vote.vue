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
	<app-content>
		<controls :intitle="event.title">
			<template slot="after">
				<button :disabled="writingPoll" class="button btn primary" @click="writePoll(poll.mode)">
					<span>{{ saveButtonTitle }}</span>
					<span v-if="writingPoll" class="icon-loading-small" />
				</button>
			</template>
		</controls>

		<div class="main-container">

			<div class="wordwrap description">
				<h2>
					{{ event.title }}
					<span v-if="event.expired"
					      class="error"> {{ t('poll', 'Expired') }} </span>
				</h2>
				<h3> {{ event.description }} </h3>
			</div>

			<div class="workbench">

				<ul name="participants" class="participants">
					<user-div :key="'currentUser_' + poll.currentUser" tag="li" :user-id="poll.currentUser" />
					<user-div v-for="(participant) in otherParticipants" :key="participant" tag="li" :user-id="participant" />
				</ul>

				<div class="vote-table">
					<transition-group v-if="event.type === 'datePoll'" name="voteOptions" tag="div" class="header">
						<date-poll-vote-header v-for="(option) in voteOptions" :key="option.text" :option="option" :poll-type="event.type" />
					</transition-group>

					<transition-group v-if="event.type === 'textPoll'" name="voteOptions" tag="div" class="header">
						<text-poll-vote-header v-for="(option) in voteOptions" :key="option.text" :option="option" :poll-type="event.type" />
					</transition-group>

					<transition-group name="votes" tag="div" class="votes">
						<div v-for="(participant) in myVotes" :key="participant.name">
							<vote-item v-for="vote in participant.votes" :key="vote.id" class="poll-cell" :option="vote" :poll-type="event.type" :edit="true"
							           @voteClick="cycleVote(vote)" />
						</div>
						<div v-for="(participant) in otherVotes" :key="participant.name">
							<vote-item v-for="vote in participant.votes" :key="vote.id" class="poll-cell" :option="vote" :poll-type="event.type" />
						</div>
					</transition-group>
				</div>

			</div>

		</div>

		<app-sidebar :title="t('polls', 'Details')">

			<app-sidebar-tab :name="t('polls', 'Comments')" icon="icon-comment">
				<comments-tab/>
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Information')" icon="icon-info">
				<information-tab/>
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Configuration')" icon="icon-settings">
				<configuration-tab/>
			</app-sidebar-tab>

		</app-sidebar>

		<!-- <loading-overlay v-if="loadingPoll" /> -->
	</app-content>
</template>

<script>
	import moment from 'moment'
	import DatePollVoteHeader from '../components/datePoll/voteHeader'
	import TextPollVoteHeader from '../components/textPoll/voteHeader'
	import InformationTab from '../components/tabs/information'
	import ConfigurationTab from '../components/tabs/configuration'
	import CommentsTab from '../components/tabs/comments'
	import VoteItem from '../components/base/voteItem'
	import { mapState, mapGetters } from 'vuex'

	export default {
		name: 'Vote',
		components: {
			DatePollVoteHeader,
			TextPollVoteHeader,
			InformationTab,
			ConfigurationTab,
			CommentsTab,
			VoteItem,
		},

		data() {
			return {
				writingPoll: false,
			}
		},

		computed: {
			...mapState({
				poll: state => state.poll,
				event: state => state.poll.event,
				comments: state => state.poll.comments,
				participants: state => state.poll.participants,
				shares: state => state.poll.shares,
				votes: state => state.poll.votes,
				voteOptions: state => state.poll.voteOptions,
			}),

			...mapGetters([
				'accessType',
				'adminMode',
				'countComments',
				'optionsVotes',
				'participantsVotes',
				'otherVotes',
				'otherParticipants',
				'myVotes',
				'timeSpanCreated',
				'timeSpanExpiration',
				'languageCode',
				'languageCodeShort',
				'localeCode',
			]),

			countCommentsHint: function() {
				return n('polls', 'There is %n comment', 'There are %n comments', this.countComments)
			},

			title: function() {
				return t('polls', 'Polls') + ' - ' + this.event.title
			},

			saveButtonTitle: function() {
				if (this.writingPoll) {
					return t('polls', 'Writing poll')
				} else if (this.poll.mode === 'edit') {
					return t('polls', 'Update poll')
				} else {
					return t('polls', 'Create new poll')
				}
			},
		},

		created() {
			moment.locale(this.localeString)
			this.$store.dispatch({
				type: 'loadPoll',
				hash: this.$route.params.hash,
				mode: 'vote',
			})
		},

		methods: {
			cycleVote(payload) {
				console.log(payload.id)
			},
		},
	}
</script>

<style lang="scss">
	.main-container {
		display: flex;
		flex-direction: column;
		flex: 1;
		flex-wrap: nowrap;
		overflow-x: hidden;
		margin-top: 45px;
		padding: 8px;

		.workbench {
			display: flex;
			flex-direction: row;
			flex-grow: 0;
			overflow-x: auto;
			padding-bottom: 10px;
			// min-height: 280px;

			.participants {
				display: flex;
				flex-direction: column;
				flex: 1 0;
				margin-top: 149px;
				border-top: 1px solid var(--color-border-dark);
				& > div {
					display: flex;
					flex-direction: row;
					flex-grow: 1;
					border-bottom: 1px solid var(--color-border-dark);
					height: 44px;
					padding: 0 17px;
					order: 2;
				}
			}
			.vote-table {
				display: flex;
				flex: 5;
				flex-direction: column;
				justify-content: flex-start;

				.header {
					display: flex;
					flex-direction: row;
					height: 150px;
					align-items: center;
					border-bottom: 1px solid var(--color-border-dark);
					& > div {
						flex: 1;
					}
				}
				.votes {
					& > div {
						display: flex;
						flex-direction: row;
						border-bottom: 1px solid var(--color-border-dark);
					}
				}
			}
		}
	}
</style>
