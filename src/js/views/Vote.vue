<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent :class="[{ closed: isPollClosed, scrolled: scrolled, 'vote-style-beta-510': useAlternativeStyling }, pollType]">
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
						<TextRankPollIcon v-else-if="pollType === 'textRankPoll'" />
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
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import VoteTable from '../components/VoteTable/VoteTable.vue'
import VoteInfoCards from '../components/Cards/VoteInfoCards.vue'
import { CardAnonymousPollHint, CardHiddenParticipants } from '../components/Cards/index.js'

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
		CardAnonymousPollHint,
		CardHiddenParticipants,
		LoadingOverlay,
		VoteTable,
		VoteInfoCards,
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
			pollType: (state) => state.poll.type,
			countOptionsInPoll: (state) => state.poll.status.countOptions,
			pollTitle: (state) => state.poll.configuration.title,
			pollDescription: (state) => state.poll.configuration.description,
			pollAnonymous: (state) => state.poll.configuration.anonymous,
			permissions: (state) => state.poll.permissions,
			useAlternativeStyling: (state) => state.settings.user.useAlternativeStyling,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
			options: 'options/rankedOptions',
			viewMode: 'poll/viewMode',
			countHiddenParticipants: 'poll/countHiddenParticipants',
		}),

		emptyContentProps() {
			if (this.countOptionsInPoll > 0) {
				return {
					name: t('polls', 'We are sorry, but there are no more vote options available'),
					description: t('polls', 'All options are booked up.'),
				}
			}

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
