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
		<HeaderBar class="area__header">
			<template #title>
				{{ poll.title }}
			</template>
			<template #right>
				<PollHeaderButtons />
			</template>
			<PollInfoLine />
		</HeaderBar>

		<div class="vote_main">
			<div v-if="poll.description" class="area__description">
				<MarkUpDescription />
			</div>

			<div v-if="acl.allowAddOptions && proposalsOpen && !closed" class="area__proposal">
				<OptionProposals />
			</div>
			<div v-if="showConfirmationMail" class="area__confirmation">
				<ActionSendConfirmedOptions />
			</div>

			<div class="area__main" :class="viewMode">
				<VoteTable v-show="options.length" :view-mode="viewMode" />

				<EmptyContent v-if="!options.length">
					<template #icon>
						<TextPollIcon v-if="poll.type === 'textPoll'" />
						<DatePollIcon v-else />
					</template>
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

			<div v-if="countHiddenParticipants" class="area__footer">
				<h2>
					{{ t('polls', 'Due to performance concerns {countHiddenParticipants} voters are hidden.', { countHiddenParticipants }) }}
				</h2>
			</div>

			<div v-if="poll.anonymous" class="area__footer">
				<div>
					{{ t('polls', 'Although participant\'s names are hidden, this is not a real anonymous poll because they are not hidden from the owner.') }}
					{{ t('polls', 'Additionally the owner can remove the anonymous flag at any time, which will reveal the participant\'s names.') }}
				</div>
			</div>
		</div>

		<PublicRegisterModal v-if="showRegisterModal" />
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { AppContent, EmptyContent } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import MarkUpDescription from '../components/Poll/MarkUpDescription.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
import HeaderBar from '../components/Base/HeaderBar.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import ActionSendConfirmedOptions from '../components/Actions/ActionSendConfirmedOptions.vue'

export default {
	name: 'Vote',
	components: {
		ActionSendConfirmedOptions,
		AppContent,
		EmptyContent,
		HeaderBar,
		MarkUpDescription,
		PollHeaderButtons,
		PollInfoLine,
		DatePollIcon,
		TextPollIcon,
		LoadingOverlay: () => import('../components/Base/LoadingOverlay.vue'),
		OptionProposals: () => import('../components/Options/OptionProposals.vue'),
		PublicRegisterModal: () => import('../components/Poll/PublicRegisterModal.vue'),
		VoteTable: () => import('../components/VoteTable/VoteTable.vue'),
	},

	data() {
		return {
			isLoading: false,
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
			acl: (state) => state.poll.acl,
			share: (state) => state.share,
			settings: (state) => state.settings,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			options: 'options/rankedOptions',
			viewMode: 'poll/viewMode',
			proposalsAllowed: 'poll/proposalsAllowed',
			proposalsOpen: 'poll/proposalsOpen',
			countHiddenParticipants: 'poll/countHiddenParticipants',
			safeTable: 'poll/safeTable',
			confirmedOptions: 'options/confirmed',
		}),

		showConfirmationMail() {
			return this.acl.isOwner && this.closed && this.confirmedOptions.length > 0
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.poll.title}`
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
		}

		if (this.$route.name === 'publicVote' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
			document.body.classList.add('theme--dark')
		}

		emit('polls:sidebar:toggle', { open: (window.innerWidth > 920) })
	},

	beforeDestroy() {
		this.$store.dispatch({ type: 'poll/reset' })
	},

	methods: {
		openOptions() {
			emit('polls:sidebar:toggle', { open: true, activeTab: 'options' })
		},
	},
}

</script>

<style lang="scss">
.vote_main {
	display: flex;
	flex-direction: column;
	row-gap: 8px;
}

.vote_head {
	display: flex;
	flex-wrap: wrap-reverse;
	justify-content: flex-end;
	.poll-title {
		flex: 1 270px;
	}
}

.area__main {
	display: flex;
	flex-direction: column;
}

.area__proposal .mx-input-wrapper > button {
	width: initial;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}
</style>
