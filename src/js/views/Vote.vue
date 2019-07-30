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
		<div class="main-container">
			<controls :intitle="event.title">
				<template slot="after">
					<button :disabled="writingPoll" class="button btn primary" @click="write()">
						<span>{{ saveButtonTitle }}</span>
						<span v-if="writingPoll" class="icon-loading-small" />
					</button>
				</template>
			</controls>

			<div v-if="poll.mode === 'vote'">
				<h2><span v-if="event.expired" class="label error">{{ t('poll', 'Expired') }}</span>{{ event.title }}</h2>
				<h3> {{ event.description }} </h3>
			</div>

			<div v-if="poll.mode === 'edit'" class="editDescription">
				<input v-model="eventTitle" :class="{ error: titleEmpty }" type="text">
				<textarea id="pollDesc" :value="event.description" @input="updateDescription" />
			</div>

			<vote-table />
			<notification />
		</div>

		<app-sidebar :title="t('polls', 'Details')">
			<template slot="primary-actions">
				<button v-if="allowEdit" class="button btn primary" :class="{ warning: adminMode }"
					@click="toggleEdit()"
				>
					<span>{{ editButtonTitle }}</span>
				</button>
			</template>

			<app-sidebar-tab :name="t('polls', 'Comments')" icon="icon-comment">
				<comments-tab />
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Information')" icon="icon-info">
				<information-tab />
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Configuration')" icon="icon-settings">
				<configuration-tab />
			</app-sidebar-tab>
		</app-sidebar>
	</app-content>
</template>

<script>
import moment from 'moment'
import Notification from '../components/base/notification'
import InformationTab from '../components/tabs/information'
import ConfigurationTab from '../components/tabs/configuration'
import CommentsTab from '../components/tabs/comments'
import VoteTable from '../components/voteTable'
import { mapState, mapGetters, mapActions, mapMutations } from 'vuex'

export default {
	name: 'Vote',
	components: {
		Notification,
		InformationTab,
		ConfigurationTab,
		CommentsTab,
		VoteTable
	},

	data() {
		return {
			writingPoll: false
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			event: state => state.event,
			shares: state => state.poll.shares
		}),

		...mapGetters([
			'adminMode',
			'languageCodeShort',
			'localeCode',
			'timeSpanCreated',
			'timeSpanExpiration'
		]),

		eventTitle: {
			get() {
				return this.event.title
			},
			set(value) {
				this.$store.commit('eventSetProperty', { property: 'title', value: value })
			}
		},

		loggedIn() {
			return (OC.getCurrentUser() !== '')
		},

		adminMode() {
			return (this.event.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
		},

		allowEdit() {
			return this.event.owner === OC.getCurrentUser() || this.adminMode
		},

		editButtonTitle() {
			if (this.poll.mode === 'vote') {
				return t('polls', 'Edit mode')
			} else if (this.poll.mode === 'edit') {
				return t('poll', 'Vote mode')
			} else {
				return 'Oops'
			}
		},

		title: function() {
			return t('polls', 'Polls') + ' - ' + this.event.title
		},
		titleEmpty() {
			return (this.event.title.trim().length === 0)
		},

		saveButtonTitle: function() {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				return t('polls', 'Update poll')
			} else if (this.poll.mode === 'vote') {
				return t('polls', 'Vote!')
			} else {
				return t('polls', 'Create new poll')
			}
		}
	},

	created() {
		moment.locale(this.localeString)
		this.$store.dispatch({
			type: 'loadPoll',
			hash: this.$route.params.hash,
			mode: 'vote'
		})
		.then(() => {
			this.$store.dispatch({ type: 'loadEvent', pollId: this.poll.id, mode: 'vote' })
			this.$store.dispatch({ type: 'loadOptions', pollId: this.poll.id, })
			this.$store.dispatch({ type: 'loadVotes', pollId: this.poll.id, mode: 'vote', currentUser: this.poll.currentUser })
			this.$store.dispatch({ type: 'loadComments', pollId: this.poll.id })
		})
	},

	methods: {
		...mapMutations({
			addNewPollText: 'textAdd'
		}),

		...mapActions([
			'addMe',
			'writePollPromise'
		]),

		updateDescription(e) {
			this.$store.commit('eventSetProperty', { property: 'description', value: e.target.value })
		},

		toggleEdit() {
			if (this.poll.mode === 'vote') {
				this.$store.commit('pollSetProperty', { property: 'mode', value: 'edit' })
			} else if (this.poll.mode === 'edit') {
				this.$store.commit('pollSetProperty', { property: 'mode', value: 'vote' })
			}
		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'))
			} else {
				this.writingPoll = true
				this.writeEventPromise()
				this.writeOptionsPromise()
				this.writingPoll = false
				OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.event.title))
				// this.writingPoll = false
				// OC.Notification.showTemporary(t('polls', 'Error on saving poll, see console'))
			}
		},

		writeVote() {
			if (this.poll.currentUser.length < 4) {
				OC.Notification.showTemporary(
					t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).')
				)
			} else {
				this.writingVote = true
				this.$store
					.dispatch('writeVotePromise')
					.then(response => {
						this.writingVote = false
						OC.Notification.showTemporary(t('polls', 'Vote successfully saved', 1, this.event.title))
					})
					.catch(error => {
						this.writingVote = false
						/* eslint-disable-next-line no-console */
						console.log('Error while saving vote - Error: ', error.response)
						OC.Notification.showTemporary(t('polls', 'Error while saving vote', { type: 'error' }))
					})
			}
		},

		write() {
			if (this.poll.mode === 'edit') {
				this.writePoll()
			} else if (this.poll.mode === 'vote') {
				this.writeVote()
			}

		}
	}
}
</script>

<style lang="scss" scoped>

	.main-container {
		display: flex;
		flex-direction: column;
		flex: 1;
		flex-wrap: nowrap;
		overflow-x: hidden;
		padding: 8px;

		.editDescription {
			min-width: 245px;
			max-width: 540px;
			display: flex;
			flex-direction: column;
			flex: 0;
			padding: 8px;
			& > * {
				width: auto;
				flex: 1 1 auto;
			}
		}
	}

</style>
