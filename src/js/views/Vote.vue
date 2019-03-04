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
	<div id="app-content">
		<controls :intitle="poll.event.title">
			<template slot="after">
				<button :disabled="writingPoll" class="button btn primary" @click="writePoll(poll.mode)">
					<span>{{ saveButtonTitle }}</span>
					<span v-if="writingPoll" class="icon-loading-small" />
				</button>
				<button class="button symbol icon-settings" @click="switchSidebar" />
			</template>
		</controls>

		<div class="main-container">
			<div class="wordwrap description">
				<span> {{ poll.event.description }} </span>
				<span v-if="poll.event.expired" class="warning"> {{ t('poll', 'The poll expired on %s. Voting is disabled, but you can still comment.', 1, poll.event.expirationDate) }} </span>
			</div>

			<div class="workbench">

				<ul class="participants">
					<li v-for="participant in poll.participants">
						<user-div :user-id="participant" />
					</li>
				</ul>

				<div class="table">
					<ul	v-if="poll.event.type === 'datePoll'"	class="header">
						<li	v-for="pollDate in poll.options.pollDates">
							<vote-date-poll :option="pollDate" :pollType="poll.event.type" :header="true"/>
						</li>
					</ul>
					<ul	v-if="poll.event.type === 'textPoll'"	class="header">
						<li	v-for="pollText in poll.options.pollTexts">
							{{ pollText.text }}
						</li>
					</ul>
					<ul	class="votes">
						<li	v-for="(participant, index) in poll.participants">
							<ul>
								<li v-for="vote in usersVotes(participant)">
									{{ vote }}
								</li>
							</ul>
						</li>
					</ul>

				</div>
			</div>
		</div>

		<side-bar v-if="sidebar">
			<UserDiv :user-id="poll.event.owner" :description="t('polls', 'Owner')" />

			<ul class="tabHeaders">
				<li class="tabHeader selected" data-tabid="configurationsTabView" data-tabindex="0">
					<a href="#">
						{{ t('polls', 'Comments') }}
					</a>
				</li>
			</ul>
		</side-bar>

		<loading-overlay v-if="loadingPoll" />


	</div>
</template>

<script>
import moment from 'moment'
import sortBy from 'lodash/sortBy'
import voteDatePoll from '../components/voteDatePoll'
// import voteUsersVotes from '../components/voteUsersVotes'

export default {
	name: 'Vote',
	components: {
		voteDatePoll
	},

	data() {
		return {
			poll: {
				mode: 'vote',
				comments: [],
				votes: [],
				shares: [],
				grantedAs: 'owner',
				id: 0,
				result: 'new',
				event: {
					id: 0,
					hash: '',
					type: 'datePoll',
					title: '',
					description: '',
					created: '',
					access: 'public',
					expiration: false,
					expirationDate: '',
					expired: false,
					isAnonymous: false,
					fullAnonymous: false,
					allowMaybe: false,
					owner: undefined
				},
				options: {
					pollDates: [],
					pollTexts: []
				}
			},
			system: [],
			lang: '',
			locale: '',
			placeholder: '',
			nextPollDateId: 1,
			nextPollTextId: 1,
			protect: false,
			writingPoll: false,
			loadingPoll: true,
			sidebar: false,
			titleEmpty: false,
			indexPage: '',
			longDateFormat: '',
			dateTimeFormat: '',
			lastVoteId: 0
		}
	},

	computed: {
		filteredVotes() {
			var voteList = Array()
			var optionsList = Array()
			var voteText

			if (this.poll.event.type === 'datePoll') {
				optionsList = this.poll.options.pollDates
			} else {
				optionsList = this.poll.options.pollTexts
			}
			for (var i = 0; i < this.poll.participants.length; ++i) {
				var participant = this.poll.participants[i]

				for (var j = 0; j < optionsList.length; ++j) {

					if (this.poll.event.type === 'datePoll') {
						var foundOption = this.poll.votes.filter(obj => {
							voteText = optionsList[j].timestamp
							return obj.userId === participant && moment.utc(obj.voteOptionText).unix() === optionsList[j].timestamp
						})
					} else {
						var foundOption = this.poll.votes.filter(obj => {
							voteText = optionsList[j].text
							return obj.userId === participant && obj.voteOptionText === optionsList[j].text
						})
					}

					if (foundOption.length > 0) {
						voteList.push(foundOption[foundOption.length -1])
					} else {
						console.log(voteText)
						voteList.push({
							'id':++this.lastVoteId,
							'userId': this.poll.participants[i],
							'voteOptionId': optionsList[j].id,
							'voteOptionText': voteText,
							'voteAnswer': 'no'
						})
					}
				}
			}
			return voteList
		},

		adminMode() {
			return (this.poll.event.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
		},

		langShort() {
			return this.lang.split('-')[0]
		},

		title() {
			return t('polls', 'Polls') + ' - ' + this.poll.event.title
		},
		saveButtonTitle() {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				return t('polls', 'Update poll')
			} else {
				return t('polls', 'Create new poll')
			}
		},

		localeData() {
			return moment.localeData(moment.locale(this.locale))
		},

	},

	created() {
		this.indexPage = OC.generateUrl('apps/polls/')
		this.getSystemValues()
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
		this.loadPoll(this.$route.params.hash)

		if (window.innerWidth > 1024) {
			this.sidebar = true
		}
	},

	methods: {
		usersVotes(userId, options) {
			return this.filteredVotes.filter((element) => {
				return element.userId === userId
	       	})
		},
		switchSidebar() {
			this.sidebar = !this.sidebar
		},

		getSystemValues() {
			this.$http.get(OC.generateUrl('apps/polls/get/system'))
				.then((response) => {
					this.system = response.data.system
				}, (error) => {
					this.poll.event.hash = ''
					/* eslint-disable-next-line no-console */
					console.log(error.response)
				})
		},

		addNewPollDate(newPollDate) {
			if (newPollDate != null) {
				this.newPollDate = moment(newPollDate)
				this.poll.options.pollDates.push({
					id: this.nextPollDateId++,
					timestamp: moment(newPollDate).unix()
				})
				this.poll.options.pollDates = sortBy(this.poll.options.pollDates, 'timestamp')
			}
		},

		loadPoll(hash) {
			this.loadingPoll = true
			this.$http.get(OC.generateUrl('apps/polls/get/poll/' + hash))
				.then((response) => {
					this.poll = response.data
					if (this.poll.event.expirationDate !== null) {
						this.poll.event.expirationDate = new Date(moment.utc(this.poll.event.expirationDate))
					} else {
						this.poll.event.expirationDate = ''
					}

					if (this.poll.event.type === 'datePoll') {
						var i
						for (i = 0; i < this.poll.options.pollTexts.length; i++) {
							this.addNewPollDate(new Date(moment.utc(this.poll.options.pollTexts[i].text)))
						}
						this.poll.options.pollTexts = []
					}

					this.loadingPoll = false
					this.newPollDate = ''
					this.newPollText = ''
					this.lastVoteId = Math.max.apply(Math, this.poll.votes.map(function(o) { return o.id; }))
				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
					this.poll.event.hash = ''
					this.loadingPoll = false
				})
		}
	}
}
</script>

<style lang="scss">


.main-container {
    display: flex;
	flex-direction: column;
    flex-grow: 1;
    flex-wrap: nowrap;
    overflow-x: hidden;
	margin-top: 45px;

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
			flex: 0 0;
			margin-top: 149px;
			border-top: 1px solid var(--color-border-dark);
			&> li {
				border-bottom: 1px solid var(--color-border-dark);
				height: 44px;
				padding: 0 17px;
				order: 2;
			}
		}
		.table {
			display: flex;
			flex: 1;
			flex-direction: column;
			justify-content: flex-start;

			.header {
				display: flex;
				flex-direction: row;
				height: 150px;
				align-items: center;
				border-bottom: 1px solid var(--color-border-dark);
				&> li {
					flex: 1;
				}
			}
		}
	}
}


</style>
