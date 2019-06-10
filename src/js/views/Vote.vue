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
		<controls :intitle="poll.event.title">
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
					{{ poll.event.title }}
					<span v-if="poll.event.expired" class="error"> {{ t('poll', 'Expired') }} </span>
				</h2>
				<h3> {{ poll.event.description }} </h3>

			</div>

			<div class="workbench">
				<ul name="participants" class="participants">
					<user-div
						v-for="(participant) in poll.participants"
						tag="li"
						:key="participant"
						:user-id="participant"
					/>
				</ul>

				<div class="vote-table">
					<transition-group
						v-if="poll.event.type === 'datePoll'"
						name="voteOptions"
						tag="div"
						class="header"
					>
						<div
							is="date-poll-vote-header"
							v-for="(option) in poll.voteOptions"
							:key="option.text"
							:option="option"
							:poll-type="poll.event.type"
						/>
					</transition-group>

					<transition-group
						v-if="poll.event.type === 'textPoll'"
						name="voteOptions"
						tag="div"
						class="header"
					>
						<div
							is="text-poll-vote-header"
							v-for="(option) in poll.voteOptions"
							:key="option.text"
							:option="option"
							:poll-type="poll.event.type"
						/>
					</transition-group>

					<transition-group
						name="votes"
						tag="div"
						class="votes"
					>
						<div
							v-for="(participant, index) in participantsVotes"
							:key="index"
						>
							<div
								is="vote-item"
								v-for="vote in participant.votes"
								:key="vote.id"
								class="poll-cell"
								:option="vote"
								:poll-type="poll.event.type"
							/>
						</div>
					</transition-group>
				</div>
			</div>
		</div>

		<app-sidebar :title="t('polls', 'Details')">

			<template slot="primary-actions">
			</template>

			<app-sidebar-tab :name="t('polls', 'Comments')" icon="icon-comment">
			</app-sidebar-tab>
			<app-sidebar-tab :name="t('polls', 'Information')" icon="icon-info">
				<user-div :user-id="poll.event.owner" :description="t('polls', 'Owner')" />
				<h3> {{ t('polls', 'Title') }} </h3>
				<div>{{ poll.event.title }}</div>
				<h3> {{ t('polls', 'Description') }} </h3>
				<div>{{ poll.event.description }}</div>
				<h3> {{ t('polls', 'Access') }} </h3>
				<div>{{ poll.event.access }}</div>
				<div>{{ poll.event.hash }}</div>
				<div>{{ accessType }}</div>
				<h3> {{ t('polls', 'Created') }} </h3>
				<div>{{ timeSpanCreated }}</div>
				<h3> {{ t('polls', 'Expires') }} </h3>
				<div>{{ timeSpanExpiration }}</div>
				<div>{{ countCommentsHint }}</div>
			</app-sidebar-tab>
		</app-sidebar>

		<!-- <loading-overlay v-if="loadingPoll" /> -->
	</app-content>
</template>

<script>
import moment from 'moment';
import DatePollVoteHeader from '../components/datePoll/voteHeader';
import TextPollVoteHeader from '../components/textPoll/voteHeader';
import VoteItem from '../components/base/voteItem';
import { mapState, mapGetters } from 'vuex';

export default {
	name: 'Vote',
	components: {
		DatePollVoteHeader,
		TextPollVoteHeader,
		VoteItem
	},

	data() {
		return {
			system: [],
			lang: '',
			locale: '',
			placeholder: '',
			nextPollDateId: 1,
			nextPollTextId: 1,
			protect: false,
			writingPoll: false,
			loadingPoll: true,
			titleEmpty: false,
			indexPage: '',
			longDateFormat: '',
			dateTimeFormat: '',
			lastVoteId: 0
		}
	},


	computed:	 {
		...mapState({
			poll: state => state.poll.poll
		}),

		...mapGetters([
			'participantsVotes',
			'accessType',
			'timeSpanCreated',
			'timeSpanExpiration',
			'optionsVotes',
			'adminMode'
		]),

		countCommentsHint: function () {
			return n('polls', 'There is %n comment', 'There are %n comments', this.poll.comments.length)
		},

		langShort: function () {
			return this.lang.split('-')[0]
		},

		title: function () {
			return t('polls', 'Polls') + ' - ' + this.poll.event.title
		},

		saveButtonTitle: function () {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				return t('polls', 'Update poll')
			} else {
				return t('polls', 'Create new poll')
			}
		},

		localeData: function () {
			return moment.localeData(moment.locale(this.locale))
		}

	},

	created() {
		this.indexPage = OC.generateUrl('apps/polls/')
		this.lang = OC.getLanguage()

		try {
			this.locale = OC.getLocale()
		} catch (e) {
			if (e instanceof TypeError) {
				this.locale = this.lang
			} else {
				/* eslint-disable-next-line no-console */
				console.log(e)
			}
		}
		moment.locale(this.locale)
		this.longDateFormat = moment.localeData().longDateFormat('L')
		this.dateTimeFormat = moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT')
		this.$store.dispatch({
			type: 'loadPoll',
			hash: this.$route.params.hash
		})

	}
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
		min-height: 280px;

		.participants {
			display: flex;
			flex-direction: column;
			flex: 1 0;
			margin-top: 149px;
			border-top: 1px solid var(--color-border-dark);
			&> div {
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
				&> div {
					flex: 1;
				}
			}
			.votes {
				&> div {
					display: flex;
					flex-direction: row;
					border-bottom: 1px solid var(--color-border-dark);
				}
			}
		}
	}
}

</style>
