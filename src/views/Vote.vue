<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent :class="[{ closed: pollStore.isClosed, scrolled: scrolled, 'vote-style-beta-510': preferencesStore.user.useAlternativeStyling }, pollStore.type]">
		<HeaderBar class="area__header">
			<template #title>
				{{ pollStore.configuration.title }}
			</template>

			<template #right>
				<PollHeaderButtons />
			</template>

			<PollInfoLine />
		</HeaderBar>

		<div class="vote_main">
			<VoteInfoCards />

			<div v-if="pollStore.configuration.description" class="area__description">
				<MarkUpDescription />
			</div>

			<div class="area__main" :class="pollStore.viewMode">
				<VoteTable v-show="optionsStore.rankedOptions.length" :view-mode="pollStore.viewMode" />

				<NcEmptyContent v-if="!optionsStore.rankedOptions.length"
					v-bind="emptyContentProps">
					<template #icon>
						<TextPollIcon v-if="pollStore.type === 'textPoll'" />
						<DatePollIcon v-else />
					</template>
					<template #action>
						<ActionOpenOptionsSidebar v-if="pollStore.permissions.edit" />
					</template>
				</NcEmptyContent>
			</div>

			<div class="area__footer">
				<CardHiddenParticipants v-if="pollStore.countHiddenParticipants" />
				<CardAnonymousPollHint v-if="pollStore.configuration.anonymous" />
			</div>
		</div>

		<LoadingOverlay v-if="isLoading" />
	</NcAppContent>
</template>

<script>
import { mapStores } from 'pinia'
import { NcAppContent, NcEmptyContent } from '@nextcloud/vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
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
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../stores/poll.ts'
import { useSessionStore } from '../stores/session.ts'
import { useOptionsStore } from '../stores/options.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Logger } from '../helpers/index.js'

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
		...mapStores(usePollStore, useSessionStore, useOptionsStore, usePreferencesStore),

		emptyContentProps() {
			if (this.pollStore.status.countOptions > 0) {
				return {
					name: t('polls', 'We are sorry, but there are no more vote options available'),
					description: t('polls', 'All options are booked up.'),
				}
			}

			return {
				name: t('polls', 'No vote options available'),
				description: this.sessionStore.pollPermissions.edit ? '' : t('polls', 'Maybe the owner did not provide some until now.'),
			}
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.PollTitle}`
		},
	},

	watch: {
		$route(to, from) {
			Logger.debug('Route changed', this.sessionStore.router)
			this.pollStore.load()
		},
	},

	created() {
		subscribe('polls:poll:load', this.pollStore.load())
		emit('polls:transition:off', 500)
	},

	mounted() {
		this.scrollElement = document.getElementById('app-content-vue')
		this.scrollElement.addEventListener('scroll', this.handleScroll)
		Logger.debug('Poll view mounted', this.sessionStore.router)
		this.loadPoll()
	},

	beforeDestroy() {
		this.scrollElement.removeEventListener('scroll', this.handleScroll)
		this.pollStore.reset()
		unsubscribe('polls:poll:load')
	},

	methods: {
		handleScroll() {
			if (this.scrollElement.scrollTop > 20) {
				this.scrolled = true
			} else {
				this.scrolled = false
			}
		},
		loadPoll() {
			// TODO: remove temporary action against race condition:
			// SessionStore must be loaded before pollStore
			setTimeout(() => {
				this.pollStore.load()
			}, 500);
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
