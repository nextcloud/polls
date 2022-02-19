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
		<HeaderBar>
			<template #left>
				<div id="header_poll_title">
					{{ poll.title }}
				</div>
			</template>
			<template #right>
				<PollHeaderButtons />
			</template>
			<template #bottom>
				<PollTitle hide-title show-sub-text />
			</template>
		</HeaderBar>
		<div class="vote_main">
			<div v-if="poll.description" class="area__header">
				<MarkUpDescription />
			</div>

			<div v-if="acl.allowAddOptions && proposalsOpen && !closed" class="area__proposal">
				<OptionProposals />
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
import MarkUpDescription from '../components/Poll/MarkUpDescription'
import PollTitle from '../components/Poll/PollTitle'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons'
import HeaderBar from '../components/Base/HeaderBar'

export default {
	name: 'Vote',
	components: {
		AppContent,
		EmptyContent,
		HeaderBar,
		MarkUpDescription,
		PollHeaderButtons,
		PollTitle,
		LoadingOverlay: () => import('../components/Base/LoadingOverlay'),
		OptionProposals: () => import('../components/Options/OptionProposals'),
		PublicRegisterModal: () => import('../components/Poll/PublicRegisterModal'),
		VoteTable: () => import('../components/VoteTable/VoteTable'),
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
			pollTypeIcon: 'poll/typeIcon',
			viewMode: 'poll/viewMode',
			proposalsAllowed: 'poll/proposalsAllowed',
			proposalsOpen: 'poll/proposalsOpen',
			countHiddenParticipants: 'poll/countHiddenParticipants',
			safeTable: 'poll/safeTable',
		}),

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

.vote_head {
	display: flex;
	flex-wrap: wrap-reverse;
	// margin-bottom: 16px;
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
