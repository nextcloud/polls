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
	<AppContent>
		<div v-if="event.id > 0" class="main-container">
			<a v-if="!sideBarOpen" href="#" class="icon icon-settings active"
				:title="t('polls', 'Open Sidebar')" @click="toggleSideBar()" />

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

			<div v-if="!isValidUser">
				<label>
					{{ t('polls', 'To participate in this poll, you have to provide a username with at least 3 letters.') }}
				</label>

				<form>
					<input v-model="userName" :class="{ error: !isValidName }" type="text" :placeholder="t('polls', 'Choose your username')">
					<input v-show="!checkingUserName" class="icon-confirm" @click="writeUserName">
					<span v-show="checkingUserName" class="icon-loading-small" style="float:right;" />
					<span v-show="!checkingUserName">{{ token }} </span>

				</form>
			</div>

			<VoteTable v-show="!loading" @voteSaved="indicateVoteSaved()" />
			<Notification />
		</div>

		<AppSidebar v-if="sideBarOpen" :active="initialTab" :title="t('polls', 'Details')"
			@close="toggleSideBar">
			<template slot="primary-actions">
				<UserDiv :user-id="event.owner" :description="t('polls', 'Owner')" />
			</template>

			<AppSidebarTab :name="t('polls', 'Comments')" icon="icon-comment">
				<SideBarTabComments />
			</AppSidebarTab>
		</AppSidebar>
		<LoadingOverlay v-if="loading" />
	</AppContent>
</template>

<script>
import Notification from '../components/notification/notification'
import VoteTable from '../components/VoteTable/VoteTable'
import SideBarTabComments from '../components/SideBar/SideBarTabComments'
import { mapState, mapGetters } from 'vuex'
import { AppSidebar, AppSidebarTab } from '@nextcloud/vue'

export default {
	name: 'Vote',
	components: {
		Notification,
		SideBarTabComments,
		VoteTable,
		AppSidebar,
		AppSidebarTab
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			sideBarOpen: false,
			loading: false,
			checkingUserName: false,
			token: '',
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
			'allowEdit'
		]),

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.event.title
		},

		isValidUser() {
			return (this.event.acl.userId !== '' && this.event.acl.userId !== null)
		},

		isValidName() {
			return false
		}

	},

	watch: {
		'$route'(to, from) {
			this.loadPoll()
		}
	},

	mounted() {
		this.loadPoll()
	},

	methods: {
		loadPoll() {
			this.loading = false
			// this.$store.dispatch('getShareAsync', { token: this.$route.params.token })
			// 	.then((response) => {
					this.$store.dispatch('loadEvent', { token: this.$route.params.token })
						.then((response) => {
							this.$store.dispatch('loadPoll', { token: this.$route.params.token })
								.then(() => {
									this.loading = false
								})
						})
						.catch((error) => {
							console.error(error)
							this.loading = false
						})
				// })
		},

		writeUserName() {
			this.checkingUsername = true
			this.$store.dispatch('addShareFromUser', {token: this.$route.params.token, userName: this.userName})
				.then((response) => {
					this.token = response.data.token
				})

		},

		toggleSideBar() {
			this.sideBarOpen = !this.sideBarOpen
		},

		openConfigurationTab() {
			this.initialTab = 'configuration'
			this.sideBarOpen = true
			this.$store.commit('pollSetProperty', { 'mode': 'edit' })
		},

		openOptionsTab() {
			if (this.event.type === 'datePoll') {
				this.initialTab = 'date-options'
			} else if (this.event.type === 'textPoll') {
				this.initialTab = 'text-options'
			}
			this.sideBarOpen = true
			this.$store.commit('pollSetProperty', { 'mode': 'edit' })
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
		h1, h2, h3, h4 {
			margin-left: 24px;
		}
	}

	.icon.icon-settings.active {
		display: block;
		width: 44px;
		height: 44px;
		right: 0;
		position: absolute;
	}

</style>
