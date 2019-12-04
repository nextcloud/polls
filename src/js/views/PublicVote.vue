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

			<div v-if="!isValidUser" class="get-username">
				<!-- <label>
					{{ t('polls', 'Enter a valid username, to participate in this poll.') }}
				</label> -->

				<form v-if="!redirecting">
					<input v-model="userName" :class="{ error: (!isValidName && userName.length > 0), success: isValidName }" type="text"
						:placeholder="t('polls', 'Enter a valid username with at least 3 Characters')">
					<input v-show="isValidName && !checkingUserName" class="icon-confirm" :class="{ error: !isValidName, success: isValidName }"
						@click="writeUserName">
					<span v-show="checkingUserName" class="icon-loading-small" />
					<!-- <span v-if="!isValidName" class="error"> {{ invalidUserNameMessage }} </span> -->
				</form>
				<div v-else>
					<span>{{ t('polls', 'You will be redirected to your personal share.') }}</span>
					<span>
						{{ t('polls', 'If you are not redirected to your poll click this link:') }}
						<router-link :to="{ name: 'publicVote', params: { token: token }}">
							Link
						</router-link>
					</span>
				</div>
			</div>
			<div v-if="displayLink" class="personal-link">
				{{ t('polls', 'Your personal link to this poll: %n', 1, personalLink) }}
				<a class="icon icon-clippy" @click="copyLink( { url: OC.generateUrl($route.path) } )" />
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
import debounce from 'lodash/debounce'
import axios from 'nextcloud-axios'
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
			userName: '',
			isValidName: false,
			invalidUserNameMessage: '',
			redirecting: false,
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

		personalLink() {
			return location.protocol.concat('//', window.location.hostname, OC.generateUrl(this.$route.path))
		},

		displayLink() {
			return (this.event.acl.userId !== '' && this.event.acl.userId !== null && this.event.acl.foundByToken)
		},

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.event.title
		},

		isValidUser() {
			return (this.event.acl.userId !== '' && this.event.acl.userId !== null)
		}

	},

	watch: {
		'$route'(to, from) {
			this.loadPoll()
		},
		userName: function() {
			if (this.userName.length > 2) {
				this.isValidName = this.validatePublicUsername()
			} else {
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your user name!')
				this.isValidName = false
			}
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
		},

		copyLink(payload) {
			this.$copyText(window.location.origin + payload.url).then(
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Link copied to clipboard'), { type: 'success' })
				},
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Error while copying link to clipboard'), { type: 'error' })
				}
			)
		},

		validatePublicUsername:	debounce(function() {
			if (this.userName.length > 2) {
				this.checkingUserName = true
				return axios.post(OC.generateUrl('apps/polls/check/username'), { pollId: this.event.id, userName: this.userName, token: this.$route.params.token })
					.then((response) => {
						this.checkingUserName = false
						this.isValidName = true
						this.invalidUserNameMessage = 'User name is OK.'
						return true
					})
					.catch(() => {
						this.checkingUserName = false
						this.isValidName = false
						this.invalidUserNameMessage = t('polls', 'This user name can not be choosed.')
						return false
					})
			} else {
				this.checkingUserName = false
				this.isValidName = false
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your user name!')
				return false
			}
		}, 500),

		writeUserName() {
			if (this.validatePublicUsername()) {
				this.$store.dispatch('addShareFromUser', { token: this.$route.params.token, userName: this.userName })
					.then((response) => {
						this.token = response.token
						this.redirecting = true
						this.$router.replace({ name: 'publicVote', params: { 'token': response.token } })
					})
					.catch(() => {
						OC.Notification.showTemporary(t('polls', 'Error saving user name"', 1, event.title), { type: 'error' })
					})
			}
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

	.personal-link {
		display: flex;
		padding: 4px 12px;
		margin: 0 12px 0 24px;
		border: 2px solid var(--color-success);
		background-color: #d6fdda !important;
		border-radius: var(--border-radius);
		font-size: 1.2em;
		opacity: 0.8;
		.icon {
			margin: 0 12px;
		}
	}
	.get-username {
		& > label {
			margin-right: 12px;
		}

		margin: 0 12px 12px 24px;
		border:2px solid var(--color-border-dark);
		font-size: 1.2em;
		padding: 0 12px 0 12px;
		display: flex;
		align-items: center;
		border-radius: var(--border-radius);
		background-color: var(--color-background-dark);
		flex-wrap: wrap;

		form, div {
			flex: 1;
			display: flex;

		}
		input {
			flex: 1;
		}

		.icon-loading-small {
			position: relative;
			right: 24px;
			top: 0px;
		}

		input[type="text"] + .icon-confirm, input[type="text"] + .icon-loading-small {
			flex: 0;
			margin-left: -8px !important;
			border-left-color: transparent !important;
			border-radius: 0 var(--border-radius) var(--border-radius) 0 !important;
			background-clip: padding-box;
			opacity: 1;
			height: 34px;
			width: 34px;
			padding: 7px 20px;
			cursor: pointer;
			margin-right: 0;
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
