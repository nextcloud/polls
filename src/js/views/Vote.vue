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
	<NcAppContent :class="[{ closed: closed, scrolled: scrolled, 'vote-style-beta-510': useAlternativeStyling }, pollType]">
		<HeaderBar class="area__header">
			<template #title>
				{{ pollTitle }}
			</template>

			<template #right>
				<PollHeaderButtons />
			</template>

			<PollInfoLine />
		</HeaderBar>

		<div class="vote_main">
			<VoteInfoCards />

			<div v-if="pollDescription" class="area__description">
				<MarkUpDescription />
			</div>

			<div class="area__main" :class="viewMode">
				<VoteTable v-show="options.length" :view-mode="viewMode" />

				<NcEmptyContent v-if="!options.length"
					v-bind="emptyContentProps">
					<template #icon>
						<TextPollIcon v-if="pollType === 'textPoll'" />
						<DatePollIcon v-else />
					</template>
					<template #action>
						<ActionOpenOptionsSidebar v-if="permissions.edit" />
					</template>
				</NcEmptyContent>
			</div>

			<div class="area__footer">
				<CardHiddenParticipants v-if="countHiddenParticipants" />
				<CardAnonymousPollHint v-if="pollAnonymous" />
			</div>
		</div>

		<LoadingOverlay v-if="isLoading" />
	</NcAppContent>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import { NcAppContent, NcEmptyContent } from '@nextcloud/vue'
import MarkUpDescription from '../components/Poll/MarkUpDescription.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
import { HeaderBar } from '../components/Base/index.js'
import { ActionOpenOptionsSidebar } from '../components/Actions/index.js'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

export default {
	name: 'Vote',
	components: {
		NcAppContent,
		NcEmptyContent,
		HeaderBar,
		MarkUpDescription,
		PollHeaderButtons,
		PollInfoLine,
		DatePollIcon,
		TextPollIcon,
		ActionOpenOptionsSidebar,
		CardAnonymousPollHint: () => import('../components/Cards/modules/CardAnonymousPollHint.vue'),
		CardHiddenParticipants: () => import('../components/Cards/modules/CardHiddenParticipants.vue'),
		LoadingOverlay: () => import('../components/Base/modules/LoadingOverlay.vue'),
		VoteTable: () => import('../components/VoteTable/VoteTable.vue'),
		VoteInfoCards: () => import('../components/Cards/VoteInfoCards.vue'),
	},

	data() {
		return {
			isLoading: false,
			scrolled: false,
			scrollElement: null,
		}
	},

	computed: {
		...mapState({
			pollTitle: (state) => state.poll.title,
			pollType: (state) => state.poll.type,
			pollDescription: (state) => state.poll.description,
			pollAnonymous: (state) => state.poll.anonymous,
			permissions: (state) => state.poll.acl.permissions,
			useAlternativeStyling: (state) => state.settings.user.useAlternativeStyling,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			options: 'options/rankedOptions',
			viewMode: 'poll/viewMode',
			countHiddenParticipants: 'poll/countHiddenParticipants',
		}),

		emptyContentProps() {
			return {
				name: t('polls', 'No vote options available'),
				description: this.permissions.edit ? '' : t('polls', 'Maybe the owner did not provide some until now.'),
			}
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.PollTitle}`
		},
	},

	mounted() {
		this.scrollElement = document.getElementById('app-content-vue')
		this.scrollElement.addEventListener('scroll', this.handleScroll)
	},

	beforeDestroy() {
		this.scrollElement.removeEventListener('scroll', this.handleScroll)
		this.resetPoll()
	},

	methods: {
		...mapActions({
			resetPoll: 'poll/reset',
		}),

		handleScroll() {
			if (this.scrollElement.scrollTop > 20) {
				this.scrolled = true
			} else {
				this.scrolled = false
			}
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

.app-content .area__header {
	transition: all var(--animation-slow) linear;
}

.app-content.scrolled .area__header {
	box-shadow: 6px 6px 6px var(--color-box-shadow);
}

.area__proposal .mx-input-wrapper > button {
	width: initial;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

.card-with-action {
	display: flex;
	align-items: center;
	column-gap: 8px;
}

.left-card-side {
  flex: 1;
}

.right-card-side {
  flex: 0;
}

// hack
.notecard > div {
  flex: 1;
}

</style>
