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
	<AppContent :class="{ expired: isExpired }">
		<div class="header-actions">
			<Actions>
				<ActionButton :icon="sortIcon" @click="ranked = !ranked">
					{{ orderCaption }}
				</ActionButton>
			</Actions>
			<Actions>
				<ActionButton :icon="toggleViewIcon" @click="toggleView()">
					{{ viewCaption }}
				</ActionButton>
			</Actions>
			<Actions>
				<ActionButton icon="icon-polls-sidebar-toggle" @click="toggleSideBar()">
					{{ t('polls', 'Toggle Sidebar') }}
				</ActionButton>
			</Actions>
		</div>
		<div class="area__header">
			<h2 class="title">
				{{ poll.title }}
				<Badge v-if="isExpired"
					:title="dateExpiryString"
					icon="icon-calendar"
					class="error" />
				<Badge v-if="!isExpired && poll.expire"
					:title="dateExpiryString"
					icon="icon-calendar"
					class="success" />
				<Badge v-if="poll.deleted"
					:title="t('polls', 'Deleted')"
					icon="icon-delete"
					class="error" />
			</h2>
			<PollInformation />

			<!-- eslint-disable-next-line vue/no-v-html -->
			<h3 class="description" v-html="linkifyDescription">
				{{ poll.description ? linkifyDescription : t('polls', 'No description provided') }}
			</h3>
		</div>

		<div v-if="$route.name === 'publicVote' && poll.id" class="area__public">
			<PersonalLink v-if="share.userId" />
		</div>

		<div class="area__main">
			<VoteTable v-show="options.length" :table-mode="tableMode" :ranked="ranked" />
			<div v-if="!options.length" class="emptycontent">
				<div class="icon-toggle-filelist" />
				<button v-if="acl.allowEdit" @click="openOptions">
					{{ t('polls', 'There are no vote options, add some in the options section of the right sidebar.') }}
				</button>
				<div v-if="!acl.allowEdit">
					{{ t('polls', 'There are no vote options. Maybe the owner did not provide some until now.') }}
				</div>
			</div>
		</div>

		<div class="area__footer">
			<Subscription v-if="acl.allowSubscribe" />
			<ParticipantsList v-if="acl.allowSeeUsernames" />
		</div>

		<PublicRegisterModal v-if="showRegisterModal" />
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import linkifyStr from 'linkifyjs/string'
import { mapState, mapGetters } from 'vuex'
import { Actions, ActionButton, AppContent } from '@nextcloud/vue'
import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import moment from '@nextcloud/moment'
import Badge from '../components/Base/Badge'
import LoadingOverlay from '../components/Base/LoadingOverlay'
import ParticipantsList from '../components/Base/ParticipantsList'
import PersonalLink from '../components/Base/PersonalLink'
import PollInformation from '../components/Base/PollInformation'
import PublicRegisterModal from '../components/Base/PublicRegisterModal'
import Subscription from '../components/Subscription/Subscription'
import VoteTable from '../components/VoteTable/VoteTable'

export default {
	name: 'Vote',
	components: {
		Actions,
		ActionButton,
		AppContent,
		Badge,
		LoadingOverlay,
		ParticipantsList,
		PersonalLink,
		PollInformation,
		PublicRegisterModal,
		Subscription,
		VoteTable,
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			isLoading: true,
			tableMode: (window.innerWidth > 480),
			ranked: false,
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
			options: state => state.poll.options.list,
			share: state => state.poll.share,
		}),

		...mapGetters({
			isExpired: 'poll/expired',
		}),

		linkifyDescription() {
			return linkifyStr(this.poll.description)
		},

		windowTitle() {
			return t('polls', 'Polls') + ' - ' + this.poll.title
		},

		dateExpiryString() {
			return moment.unix(this.poll.expire).format('LLLL')
		},
		viewCaption() {
			if (this.tableMode) {
				return t('polls', 'Switch to mobile view')
			} else {
				return t('polls', 'Switch to desktop view')
			}
		},
		orderCaption() {
			if (this.ranked) {
				if (this.poll.type === 'datePoll') {
					return t('polls', 'Date order')
				} else {
					return t('polls', 'Original order')
				}
			} else {
				return t('polls', 'Ranked order')
			}
		},

		showRegisterModal() {
			return (this.$route.name === 'publicVote'
				&& !this.share.userId
				&& !this.isExpired
				&& this.poll.id
			)
		},

		sortIcon() {
			if (this.ranked) {
				if (this.poll.type === 'datePoll') {
					return 'icon-calendar-000'
				} else {
					return 'icon-toggle-filelist'
				}
			} else {
				return 'icon-quota'
			}
		},

		toggleViewIcon() {
			if (this.tableMode) {
				return 'icon-phone'
			} else {
				return 'icon-desktop'
			}
		},

	},

	watch: {
		$route() {
			this.loadPoll()
		},
	},

	created() {
		if (getCurrentUser() && this.$route.params.token) {
			// reroute to the internal vote page, if the user is logged in
			this.$store.dispatch('poll/share/get', { token: this.$route.params.token })
				.then((response) => {
					this.$router.replace({ name: 'vote', params: { id: response.share.pollId } })
				})
				.catch(() => {
					this.$router.replace({ name: 'notfound' })
				})
		} else {
			this.loadPoll()
			emit('toggle-sidebar', { open: (window.innerWidth > 920) })
		}
	},

	beforeDestroy() {
		this.$store.dispatch({ type: 'poll/reset' })
	},

	methods: {
		openOptions() {
			emit('toggle-sidebar', { open: true, activeTab: 'options' })
		},

		openConfiguration() {
			emit('toggle-sidebar', { open: true, activeTab: 'configuration' })
		},

		toggleSideBar() {
			emit('toggle-sidebar')
		},

		toggleView() {
			emit('transitions-off', { delay: 500 })
			this.tableMode = !this.tableMode
		},

		loadPoll() {
			this.isLoading = true
			this.$store
				.dispatch({ type: 'poll/get', pollId: this.$route.params.id, token: this.$route.params.token })
				.then((response) => {
					this.isLoading = false
					window.document.title = this.windowTitle
				})
				.catch(() => {
					this.isLoading = false
					this.$router.replace({ name: 'notfound' })
				})
		},
	},
}
</script>

<style lang="scss" scoped>
.emptycontent {
	margin: 44px 0;
}

.header-actions {
	display: flex;
	justify-content: end;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

</style>
