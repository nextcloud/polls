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
					<a v-if="!sideBarOpen" @click="toggleSideBar()" href="#" class="icon icon-settings active" :title="sideBarButtonTitle"></a>
				</template>
			</controls>

			<div>
				<h2>
					{{ event.title }}
					<span v-if="event.expired" class="label error">{{ t('polls', 'Expired') }}</span>
					<span v-if="!event.expired && event.expiration" class="label success">{{ t('polls', 'Votes are possible until %n', 1, event.expirationDate) }}</span>
					<span v-if="!event.expiration" class="label success">{{ t('polls', 'No expiration date set') }}</span>
					<transition name="fade">
						<span v-if="voteSaved" class="label success">Vote saved</span>
					</transition>
				</h2>
				<h3>
					{{ event.description }}
				</h3>
			</div>

			<vote-table v-show="!loading" @voteSaved="indicateVoteSaved()" />
			<notification v-if="loggedIn" />
		</div>

		<app-sidebar v-if="sideBarOpen" :active="initialTab" :title="t('polls', 'Details')" @close="toggleSideBar">
			<template slot="primary-actions">
				<button v-if="allowEdit" class="button btn primary" :class="{ warning: adminMode }"
					@click="toggleEdit()">
					<span>{{ editButtonTitle }}</span>
				</button>
			</template>

			<app-sidebar-tab :name="t('polls', 'Comments')" icon="icon-comment">
				<comments-tab />
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Information')" icon="icon-info">
				<information-tab />
			</app-sidebar-tab>

			<app-sidebar-tab v-if="allowEdit && event.type === 'datePoll'" :name="t('polls', 'Edit date options')" icon="icon-calendar">
				<date-options-tab />
			</app-sidebar-tab>

			<app-sidebar-tab v-if="allowEdit" :name="t('polls', 'Configuration')" icon="icon-settings">
				<configuration-tab />
			</app-sidebar-tab>
		</app-sidebar>
		<loading-overlay v-if="loading" />
	</app-content>
</template>

<script>
import moment from 'moment'
import Controls from '../components/base/controls'
import Notification from '../components/notification/notification'
import InformationTab from '../components/settings/informationTab'
import ConfigurationTab from '../components/settings/configurationTab'
import DateOptionsTab from '../components/settings/dateOptionsTab'
import CommentsTab from '../components/comments/commentsTab'
import VoteTable from '../components/vote/voteTable'
import { mapState, mapGetters, mapActions, mapMutations } from 'vuex'
import { AppSidebar, AppSidebarTab } from '@nextcloud/vue'

export default {
	name: 'Vote',
	components: {
		Notification,
		Controls,
		InformationTab,
		ConfigurationTab,
		CommentsTab,
		DateOptionsTab,
		VoteTable,
		AppSidebar,
		AppSidebarTab
	},

	data() {
		return {
			writingPoll: false,
			voteSaved: false,
			delay: 50,
			sideBarOpen: false,
			loading: false,
			initialTab: 'comments'
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
			'allowEdit',
			'languageCodeShort',
			'localeCode',
			'timeSpanCreated',
			'timeSpanExpiration'
		]),

		pollList() {
			return this.$store.state.polls.list
		},

		loggedIn() {
			return (OC.getCurrentUser() !== '')
		},

		sideBarButtonTitle() {
			return (t('polls', 'Open Sidebar'))
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

	watch: {
		'$route'(to, from) {
			this.refreshPoll()
		}
	},

	mounted() {
		this.refreshPoll()
	},

	methods: {
		...mapMutations({
			addNewPollText: 'textAdd'
		}),

		// ...mapActions([
		// 	'addMe',
		// 	'writeEventPromise'
		// ]),

		toggleSideBar() {
			this.sideBarOpen = !this.sideBarOpen
		},

		openInEditMode() {
			this.initialTab='configuration'
			this.sideBarOpen = true
			this.$store.commit('pollSetProperty', { 'mode': 'edit' })
		},

		refreshPoll() {
			this.loading = true
			moment.locale(this.localeString)
			this.$store.dispatch({ type: 'loadEvent', pollId: this.$route.params.id, mode: this.$route.name })
				.then(() => {
					this.$store.dispatch({
						type: 'loadPoll',
						pollId: this.$route.params.id,
						mode: this.$route.name
					})
						.then(() => {
							this.loading = false
							if (this.$route.name === 'edit') {
								this.openInEditMode()
							}
						})
				})

		},

		toggleEdit() {
			if (this.poll.mode === 'vote') {
				this.$store.commit('pollSetProperty', { 'mode': 'edit' })
			} else if (this.poll.mode === 'edit') {
				this.$store.commit('pollSetProperty', { 'mode': 'vote' })
			}
		},

		timer() {
			this.voteSaved = false
		},

		indicateVoteSaved() {
			this.voteSaved = true
			window.setTimeout(this.timer, this.delay)
		}
	}
}
</script>

<style lang="scss" scoped>
	.main-container {
		flex: 1;
		margin: 0;
		flex-direction: column;
		flex: 1;
		flex-wrap: nowrap;
		overflow-x: scroll;
	}

</style>
