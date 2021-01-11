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
	<AppContent :class="{ closed: closed }">
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
				<Badge v-if="closed"
					:title="t('polls', 'Closed {relativeTimeAgo}', {relativeTimeAgo: timeExpirationRelative})"
					icon="icon-polls-closed"
					:class="expiryClass" />
				<Badge v-if="!closed && poll.expire"
					:title="t('polls', 'Closing {relativeExpirationTime}', {relativeExpirationTime: timeExpirationRelative})"
					icon="icon-calendar"
					:class="expiryClass" />
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

		<div class="area__main" :class="viewMode">
			<VoteTable v-show="options.length" :view-mode="viewMode" :ranked="ranked" />

			<EmptyContent v-if="!options.length" icon="icon-toggle-filelist">
				{{ t('polls', 'No vote options available') }}
				<template #desc>
					<button v-if="acl.allowEdit" @click="openOptions">
						{{ t('polls', 'Add some!') }}
					</button>
					<div v-if="!acl.allowEdit">
						{{ t('polls', 'Maybe the owner did not provide some until now.') }}
					</div>
				</template>
			</EmptyContent>
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
import linkifyUrls from 'linkify-urls'
import { mapState, mapGetters } from 'vuex'
import { Actions, ActionButton, AppContent, EmptyContent } from '@nextcloud/vue'
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
		EmptyContent,
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
			// reloadInterval: 30000,
			voteSaved: false,
			delay: 50,
			isLoading: false,
			ranked: false,
			manualViewDatePoll: '',
			manualViewTextPoll: '',
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
			options: state => state.poll.options.list,
			share: state => state.share,
			settings: state => state.settings,
		}),

		...mapGetters({
			closed: 'poll/closed',
		}),

		viewTextPoll() {
			if (this.manualViewTextPoll) {
				return this.manualViewTextPoll
			} else {
				if (window.innerWidth > 480) {
					return this.settings.user.defaultViewTextPoll
				} else {
					return 'mobile'
				}
			}
		},

		viewDatePoll() {
			if (this.manualViewDatePoll) {
				return this.manualViewDatePoll
			} else {
				if (window.innerWidth > 480) {
					return this.settings.user.defaultViewDatePoll
				} else {
					return 'mobile'
				}
			}
		},

		viewMode() {
			if (this.poll.type === 'textPoll') {
				return this.viewTextPoll
			} else if (this.poll.type === 'datePoll') {
				return this.viewDatePoll
			} else {
				return 'desktop'
			}
		},

		linkifyDescription() {
			return linkifyUrls(this.poll.description, {
				attributes: { class: 'linkified' },
			})
		},

		windowTitle() {
			return t('polls', 'Polls') + ' - ' + this.poll.title
		},

		timeExpirationRelative() {
			if (this.poll.expire) {
				return moment.unix(this.poll.expire).fromNow()
			} else {
				return t('polls', 'never')
			}
		},

		closeToClosing() {
			return (!this.poll.closed && this.poll.expire && moment.unix(this.poll.expire).diff() < 86400000)
		},

		expiryClass() {
			if (this.closed) {
				return 'error'
			} else if (this.poll.expire && this.closeToClosing) {
				return 'warning'
			} else if (this.poll.expire && !this.closed) {
				return 'success'
			} else {
				return 'success'
			}
		},

		viewCaption() {
			if (this.viewMode === 'desktop') {
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
				&& (this.share.type === 'public'
					|| this.share.type === 'email'
					|| this.share.type === 'contact')
				&& !this.closed
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
			if (this.viewMode === 'desktop') {
				return 'icon-phone'
			} else {
				return 'icon-desktop'
			}
		},

	},

	created() {
		if (getCurrentUser() && this.$route.name === 'publicVote') {
			// reroute to the internal vote page, if the user is logged in
			this.$store.dispatch('share/get', { token: this.$route.params.token })
				.then((response) => {
					this.$router.replace({ name: 'vote', params: { id: response.share.pollId } })
				})
				.catch(() => {
					this.$router.replace({ name: 'notfound' })
				})
		} else {
			emit('toggle-sidebar', { open: (window.innerWidth > 920) })
		}
		// this.timedReload()
	},

	beforeDestroy() {
		this.$store.dispatch({ type: 'poll/reset' })
		// window.clearInterval(this.reloadTimer)
	},

	methods: {
		openOptions() {
			emit('toggle-sidebar', { open: true, activeTab: 'options' })
		},

		// timedReload() {
		// 	// reload poll list periodically
		// 	this.reloadTimer = window.setInterval(() => {
		// 		emit('load-poll', true)
		// 	}, this.reloadInterval)
		// },

		getNextViewMode() {
			if (this.settings.viewModes.indexOf(this.viewMode) < 0) {
				return this.settings.viewModes[1]
			} else {
				return this.settings.viewModes[(this.settings.viewModes.indexOf(this.viewMode) + 1) % this.settings.viewModes.length]
			}
		},

		toggleSideBar() {
			emit('toggle-sidebar')
		},

		toggleView() {
			emit('transitions-off', 500)
			if (this.poll.type === 'datePoll') {
				if (this.manualViewDatePoll) {
					this.manualViewDatePoll = ''
				} else {
					this.manualViewDatePoll = this.getNextViewMode()
				}
			} else if (this.poll.type === 'textPoll') {
				if (this.manualViewTextPoll) {
					this.manualViewTextPoll = ''
				} else {
					this.manualViewTextPoll = this.getNextViewMode()
				}
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.header-actions {
	display: flex;
	justify-content: flex-end;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

</style>
