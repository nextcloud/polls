<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { n, t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

import MarkDownDescription from '../components/Poll/MarkDownDescription.vue'
import ActionAddOption from '../components/Actions/modules/ActionAddOption.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import VoteTable from '../components/VoteTable/VoteTable.vue'
import VoteInfoCards from '../components/Cards/VoteInfoCards.vue'
import OptionsAddModal from '../components/Modals/OptionsAddModal.vue'
import ActionOpenOptionsSidebar from '../components/Actions/modules/ActionOpenOptionsSidebar.vue'
import HeaderBar from '../components/Base/modules/HeaderBar.vue'
import CardAnonymousPollHint from '../components/Cards/modules/CardAnonymousPollHint.vue'

import { usePollStore } from '../stores/poll.ts'
import { useOptionsStore } from '../stores/options.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Event } from '../Types/index.ts'
import Collapsible from '../components/Base/modules/Collapsible.vue'
import type { CollapsibleProps } from '../components/Base/modules/Collapsible.vue'
import { onBeforeRouteLeave, onBeforeRouteUpdate } from 'vue-router'
import IntersectionObserver from '../components/Base/modules/IntersectionObserver.vue'
import { useVotesStore } from '../stores/votes.ts'
import { showError } from '@nextcloud/dialogs'
import StickyDiv from '../components/Base/modules/StickyDiv.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const preferencesStore = usePreferencesStore()
const votesStore = useVotesStore()
const voteMainId = 'vote-view'
const topObserverVisible = ref(false)
const tableObserverVisible = ref(false)
const chunksLoading = ref(false)

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

async function loadChunks() {
	if (chunksLoading.value) {
		return
	}

	try {
		chunksLoading.value = true
		await nextTick()
		await votesStore.addChunk()
	} catch {
		showError(t('polls', 'Error loading more participants'))
	} finally {
		await nextTick()
		chunksLoading.value = false
	}
}

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

/*
 * In table-view, the sticky header bar and the vote header should merge
 * to a single header bar, once the vote header reaches it's sticky position ,too
 */
const mergeHeaders = computed(
	() => pollStore.viewMode === 'table-view' && !tableObserverVisible.value,
)

/*
 * Report scrolled, if the table observer is not visible except the headers
 * are merged
 */
const scrolled = computed(() => !topObserverVisible.value && !mergeHeaders.value)

const showBottomObserver = computed(
	() =>
		votesStore.countHiddenParticipants > 0
		&& pollStore.viewMode === 'table-view',
)
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

const appClass = computed(() => [
	pollStore.type,
	pollStore.viewMode,
	voteMainId,
	{
		scrolled,
		'vote-style-beta-510': preferencesStore.user.useAlternativeStyling,
	},
])
</script>

<template>
	<NcAppContent :class="appClass">
		<StickyDiv :z-index="9" sticky-top :activate-bottom-shadow="scrolled">
			<HeaderBar>
				<template #title>
					{{ pollStore.configuration.title }}
				</template>

				<template #right>
					<PollHeaderButtons />
				</template>

				<PollInfoLine />
			</HeaderBar>
		</StickyDiv>

		<div class="vote-main">
			<IntersectionObserver id="top-observer" v-model="topObserverVisible" />
			<Collapsible
				v-if="pollStore.configuration.description"
				class="sticky-left area__top"
				v-bind="collapsibleProps">
				<MarkDownDescription />
			</Collapsible>

			<VoteInfoCards class="sticky-left area__top" />

			<IntersectionObserver
				v-if="pollStore.viewMode === 'table-view'"
				id="table-observer"
				v-model="tableObserverVisible" />

			<VoteTable
				v-show="optionsStore.options.length"
				class="area__vote"
				:down-page="tableObserverVisible" />

			<IntersectionObserver
				v-if="showBottomObserver"
				id="bottom-observer"
				:loading="chunksLoading"
				@visible="loadChunks">
				<div class="clickable_load_more" @click="loadChunks">
					{{
						n(
							'polls',
							'%n participant is hidden. Click here to load more',
							'%n participants are hidden. Click here to load more',
							votesStore.countHiddenParticipants,
						)
					}}
				</div>
			</IntersectionObserver>

			<NcEmptyContent
				v-if="!optionsStore.options.length"
				v-bind="emptyContentProps">
				<template #icon>
					<TextPollIcon v-if="pollStore.type === 'textPoll'" />
					<DatePollIcon v-else />
				</template>

				<template v-if="pollStore.permissions.addOptions" #action>
					<ActionAddOption
						v-if="pollStore.type === 'datePoll'"
						:caption="t('polls', 'Add options')" />
					<ActionOpenOptionsSidebar v-else />
				</template>
			</NcEmptyContent>

			<div class="area__footer sticky-left">
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
#bottom-observer {
	display: flex;
	justify-content: center;
	position: sticky;
	left: 70px;
}

.vote-main {
	flex: 1;
	overflow: auto;
	overscroll-behavior-inline: contain;
	position: relative;
	top: -8px;

	.markdown-description {
		margin: auto;
		max-width: var(--cap-width);
	}

	& > * {
		margin-top: 0.5rem;
	}

	.area__footer,
	.area__top,
	.list-view.area__vote {
		padding: 0 8px;
	}
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
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
