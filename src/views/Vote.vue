<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

import { useHandleScroll } from '../composables/handleScroll.ts'
import MarkUpDescription from '../components/Poll/MarkUpDescription.vue'
import ActionAddOption from '../components/Actions/modules/ActionAddOption.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import VoteTable from '../components/VoteTable/VoteTable.vue'
import VoteInfoCards from '../components/Cards/VoteInfoCards.vue'
import OptionsAddModal from '../components/Modals/OptionsAddModal.vue'
import { ActionOpenOptionsSidebar } from '../components/Actions/index.ts'
import { HeaderBar } from '../components/Base/index.ts'
import {
	CardAnonymousPollHint,
	CardHiddenParticipants,
} from '../components/Cards/index.ts'

import { usePollStore, PollType } from '../stores/poll.ts'
import { useOptionsStore } from '../stores/options.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Event } from '../Types/index.ts'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const preferencesStore = usePreferencesStore()
const voteMainId = 'watched-scroll-area'
const scrolled = useHandleScroll(voteMainId)

const isLoading = ref(false)

const emptyContentProps = computed(() => {
	if (pollStore.status.countOptions > 0) {
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

onMounted(() => {
	subscribe(Event.LoadPoll, () => pollStore.load())
	emit(Event.TransitionsOff, 500)
})

onUnmounted(() => {
	pollStore.reset()
	unsubscribe(Event.TransitionsOff, () => {})
})
</script>

<template>
	<NcAppContent
		:class="[
			{
				closed: pollStore.isClosed,
				scrolled: scrolled,
				'vote-style-beta-510': preferencesStore.user.useAlternativeStyling,
			},
			pollStore.type,
			voteMainId,
		]">
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
			<VoteInfoCards v-if="!preferencesStore.user.useCardsArrangement" />

			<div
				v-if="
					pollStore.configuration.description
					&& !preferencesStore.user.useCardsArrangement
				"
				class="area__description">
				<MarkUpDescription />
			</div>

			<div v-if="preferencesStore.user.useCardsArrangement" class="top_area">
				<div
					v-if="pollStore.configuration.description"
					class="description_container">
					<div class="area__description">
						<MarkUpDescription />
					</div>
				</div>
				<div class="cards_container">
					<VoteInfoCards />
				</div>
			</div>

				<VoteTable v-show="optionsStore.list.length" />

				<NcEmptyContent
					v-if="!optionsStore.list.length"
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
				<CardHiddenParticipants v-if="pollStore.countHiddenParticipants" />
				<CardAnonymousPollHint v-if="pollStore.status.isAnonymous" />
			</div>
		</div>

		<LoadingOverlay v-if="isLoading" />
		<OptionsAddModal v-if="pollStore.permissions.addOptions" />
	</NcAppContent>
</template>

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

.top_area {
	display: flex;
	flex-wrap: wrap-reverse;
	.description_container,
	.cards_container {
		flex: 1 50rem;
	}
}

.app-content .area__header {
	transition: all var(--animation-slow) linear;
}

.app-content.scrolled .area__header {
	box-shadow: 6px 6px 6px var(--color-box-shadow);
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
