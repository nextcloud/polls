<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

import { useHandleScroll } from '../composables/handleScroll.ts'
import MarkDownDescription from '../components/Poll/MarkDownDescription.vue'
import ActionAddOption from '../components/Actions/modules/ActionAddOption.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import VoteTable from '../components/VoteTable/VoteTable.vue'
import VoteInfoCards from '../components/Cards/VoteInfoCards.vue'
import OptionsAddModal from '../components/Modals/OptionsAddModal.vue'
import { ActionOpenOptionsSidebar } from '../components/Actions/index.ts'
import { HeaderBar } from '../components/Base/index.ts'
import { CardAnonymousPollHint } from '../components/Cards/index.ts'

import { usePollStore, PollType } from '../stores/poll.ts'
import { useOptionsStore } from '../stores/options.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Event } from '../Types/index.ts'
import Collapsible from '../components/Base/modules/Collapsible.vue'
import type { CollapsibleProps } from '../components/Base/modules/Collapsible.vue'
import { onBeforeRouteLeave, onBeforeRouteUpdate } from 'vue-router'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const preferencesStore = usePreferencesStore()
const voteMainId = 'watched-scroll-area'
const scrolled = useHandleScroll(voteMainId)

const loadingOverlayProps = {
	name: t('polls', 'Loading poll…'),
	teleportTo: '#content-vue',
	loadingTexts: [
		t('polls', 'Fetching configuration…'),
		t('polls', 'Collecting elements…'),
		t('polls', 'Checking access…'),
		t('polls', 'Almost ready…'),
		t('polls', 'Do not go away…'),
		t('polls', 'This seems to be a huge poll, please be patient…'),
	],
}
const emptyContentProps = computed(() => {
	if (optionsStore.options.length > 0) {
		return {
			name: t(
				'polls',
				'We are sorry, but there are no more vote options available',
			),
			description: t('polls', 'All options are booked up.'),
		}
	}

	return {
		name: t('polls', 'No vote options available'),
		description: pollStore.permissions.addOptions
			? ''
			: t('polls', 'Maybe the owner did not provide some until now.'),
	}
})

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const windowTitle = computed(
	() => `${t('polls', 'Polls')} - ${pollStore.configuration.title}`,
)

const isShortDescription = computed(() => {
	if (!pollStore.configuration.description) {
		return true
	}
	// If less than 20 words and less than 5 lines, then it's short
	return (
		pollStore.configuration.description.split(' ').length < 20
		&& pollStore.configuration.description.split(/\r\n|\r|\n/).length < 5
	)
})

const collapsibleProps = computed<CollapsibleProps>(() => ({
	noCollapse:
		!pollStore.configuration.collapseDescription || isShortDescription.value,
	initialState: pollStore.currentUserStatus.countVotes === 0 ? 'max' : 'min',
}))

onBeforeRouteUpdate(async () => {
	pollStore.load()
	emit(Event.TransitionsOff, 500)
})

onBeforeRouteLeave(() => {
	pollStore.resetPoll()
})

onMounted(() => {
	pollStore.load()
	subscribe(Event.LoadPoll, () => pollStore.load())
	emit(Event.TransitionsOff, 500)
})

onUnmounted(() => {
	pollStore.reset()
	unsubscribe(Event.LoadPoll, () => {})
})
</script>

<template>
	<NcAppContent
		:class="[
			pollStore.type,
			pollStore.viewMode,
			voteMainId,
			{
				scrolled: scrolled,
				'vote-style-beta-510': preferencesStore.user.useAlternativeStyling,
				'fixed-table-header': preferencesStore.user.useFixedTableHeader,
			},
		]">
		<HeaderBar>
			<template #title>
				{{ pollStore.configuration.title }}
			</template>

			<template #right>
				<PollHeaderButtons />
			</template>

			<PollInfoLine />
		</HeaderBar>

		<div class="vote_main">
			<Collapsible
				v-if="pollStore.configuration.description"
				v-bind="collapsibleProps">
				<MarkDownDescription />
			</Collapsible>

			<VoteInfoCards />

			<VoteTable v-show="optionsStore.options.length" />

			<NcEmptyContent
				v-if="!optionsStore.options.length"
				v-bind="emptyContentProps">
				<template #icon>
					<TextPollIcon v-if="pollStore.type === PollType.Text" />
					<DatePollIcon v-else />
				</template>

				<template v-if="pollStore.permissions.addOptions" #action>
					<ActionAddOption
						v-if="pollStore.type === PollType.Date"
						:caption="t('polls', 'Add options')" />
					<ActionOpenOptionsSidebar v-else />
				</template>
			</NcEmptyContent>

			<div class="area__footer">
				<CardAnonymousPollHint v-if="pollStore.status.isAnonymous" />
			</div>
		</div>

		<LoadingOverlay
			:show="pollStore.meta.status === 'loading'"
			v-bind="loadingOverlayProps" />
		<OptionsAddModal v-if="pollStore.permissions.addOptions" />
	</NcAppContent>
</template>

<style lang="scss">
.table-view .vote_main {
	flex: 1;
	overflow: auto;
	overscroll-behavior-inline: contain;

	.vote-table {
		max-height: 75vh;
		min-height: 16.6rem;
	}
}

.table-view.fixed-table-header .vote_main {
	display: flex;
	flex-direction: column;
	.vote-table {
		min-height: 18rem;
	}
}

.vote_main {
	.markdown-description {
		margin: auto;
		max-width: var(--cap-width);
	}

	& > * {
		margin-top: 0.5rem;
	}
}

.vote_head {
	display: flex;
	flex-wrap: wrap-reverse;
	justify-content: flex-end;
	.poll-title {
		flex: 1 270px;
	}
}

.top_area {
	display: flex;
	flex-wrap: wrap-reverse;
	.description_container,
	.cards_container {
		flex: 1 50rem;
	}
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
