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
	<AppContent :class="[{ closed: closed }, poll.type]">
		<div class="header-actions">
			<PollInformation />
			<ActionSortOptions />
			<ActionChangeView />
			<ActionToggleSidebar v-if="acl.allowEdit || poll.allowComment" />
		</div>
		<div class="area__header">
			<h2 class="title">
				{{ poll.title }}
				<Badge v-if="closed"
					:title="t('polls', 'Closed {relativeTimeAgo}', {relativeTimeAgo: timeExpirationRelative})"
					icon="icon-polls-closed-fff"
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

			<div class="description">
				<MarkUpDescription />
				<OptionProposals v-if="acl.allowAddOptions && proposalsAllowed" />
			</div>
		</div>

		<div class="area__main" :class="viewMode">
			<VoteTable v-show="options.length" :view-mode="viewMode" />

			<EmptyContent v-if="!options.length" :icon="pollTypeIcon">
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

		<div v-if="poll.anonymous" class="area__footer">
			<div>
				{{ t('poll', 'Although participant\'s names are hidden, this is not a real anonymous poll because they are not hidden from the owner.') }}
				{{ t('poll', 'Additionally the owner can remove the anonymous flag at any time, which will reveal the participant\'s names.') }}
			</div>
		</div>

		<PublicRegisterModal v-if="showRegisterModal" />
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { mapState, mapGetters } from 'vuex'
import { AppContent, EmptyContent } from '@nextcloud/vue'
import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import moment from '@nextcloud/moment'
import Badge from '../components/Base/Badge'
import MarkUpDescription from '../components/Poll/MarkUpDescription'
import LoadingOverlay from '../components/Base/LoadingOverlay'
import PollInformation from '../components/Poll/PollInformation'
import PublicRegisterModal from '../components/Poll/PublicRegisterModal'
import VoteTable from '../components/VoteTable/VoteTable'
import ActionSortOptions from '../components/Actions/ActionSortOptions'
import ActionChangeView from '../components/Actions/ActionChangeView'
import ActionToggleSidebar from '../components/Actions/ActionToggleSidebar'
import OptionProposals from '../components/Options/OptionProposals'

export default {
	name: 'Vote',
	components: {
		ActionChangeView,
		ActionSortOptions,
		ActionToggleSidebar,
		AppContent,
		Badge,
		MarkUpDescription,
		EmptyContent,
		LoadingOverlay,
		PollInformation,
		PublicRegisterModal,
		VoteTable,
		OptionProposals,
	},

	data() {
		return {
			delay: 50,
			isLoading: false,
			voteSaved: false,
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
			share: state => state.share,
			settings: state => state.settings,
		}),

		...mapGetters({
			closed: 'poll/closed',
			options: 'options/rankedOptions',
			pollTypeIcon: 'poll/typeIcon',
			viewMode: 'settings/viewMode',
			proposalsAllowed: 'poll/proposalsAllowed',
		}),

		showEmailEdit() {
			return ['email', 'contact', 'external'].includes(this.share.type)
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

		showRegisterModal() {
			return (this.$route.name === 'publicVote'
				&& ['public', 'email', 'contact'].includes(this.share.type)
				&& !this.closed
				&& this.poll.id
			)
		},

	},

	created() {
		// simulate @media:prefers-color-scheme until it is supported for logged in users
		// This simulates the theme--dark
		// TODO: remove, when completely supported by core
		if (!window.matchMedia) {
			return true
		} else if (this.$route.name === 'publicVote' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
			document.body.classList.add('theme--dark')
		}

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
	},

	beforeDestroy() {
		this.$store.dispatch({ type: 'poll/reset' })
	},

	methods: {
		openOptions() {
			emit('toggle-sidebar', { open: true, activeTab: 'options' })
		},

		async submitEmailAddress(emailAddress) {
			try {
				await this.$store.dispatch('share/updateEmailAddress', { emailAddress: emailAddress })
				showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: emailAddress }))
			} catch {
				showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: emailAddress }))
			}
		},
	},
}

</script>

<style lang="scss" scoped>
.description {
	display: flex;
	flex-wrap: wrap;

	.markup-description {
		min-width: 275px;
		padding: 8px;
		flex: 1;
	}
	.option-proposals {
		width: 300px;
		max-width: 400px;
		min-width: 275px;
		padding: 8px;
		flex: 1 1 300px;
		border: 1px solid var(--color-polls-foreground-yes);
		border-radius: var(--border-radius);
		background-color: var(--color-polls-background-yes);
		.mx-datepicker {
			.mx-input {
				background-clip: initial !important;
			}
		}
	}
}

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
