<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref, computed, onMounted, onUnmounted } from 'vue'
	import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
	import { t } from '@nextcloud/l10n'
	
	import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
	import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

	import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
	import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
	
	import { useHandleScroll } from '../composables/handleScroll.ts'
	import MarkUpDescription from '../components/Poll/MarkUpDescription.vue'
	import PollInfoLine from '../components/Poll/PollInfoLine.vue'
	import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
	import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
	import VoteTable from '../components/VoteTable/VoteTable.vue'
	import VoteInfoCards from '../components/Cards/VoteInfoCards.vue'
	import { ActionOpenOptionsSidebar } from '../components/Actions/index.js'
	import { HeaderBar } from '../components/Base/index.js'
	import { CardAnonymousPollHint, CardHiddenParticipants } from '../components/Cards/index.js'

	import { usePollStore, PollType } from '../stores/poll.ts'
	import { useOptionsStore } from '../stores/options.ts'
	import { usePreferencesStore } from '../stores/preferences.ts'
	
	const pollStore = usePollStore()
	const optionsStore = useOptionsStore()
	const preferencesStore = usePreferencesStore()
	
	// FIXME: Fix this, since it is not 'vote-main' that is scrolled
	const voteMainId = 'vote-main'
	const scrolled = useHandleScroll(voteMainId)

	const isLoading = ref(false)

	const emptyContentProps = computed(() => {
		if (pollStore.status.countOptions > 0) {
			return {
				name: t('polls', 'We are sorry, but there are no more vote options available'),
				description: t('polls', 'All options are booked up.'),
			}
		}

		return {
			name: t('polls', 'No vote options available'),
			description: pollStore.permissions.edit ? '' : t('polls', 'Maybe the owner did not provide some until now.'),
		}
	})

	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	const windowTitle = computed(() => `${t('polls', 'Polls')} - ${pollStore.configuration.title}`)

	onMounted(() => {
		subscribe('polls:poll:load', () => pollStore.load())
		emit('polls:transitions:off', 500)
	})

	onUnmounted(() => {
		pollStore.reset()
		unsubscribe('polls:poll:load', () => pollStore.load())
	})
</script>

<template>
	<NcAppContent :class="[{ closed: pollStore.isClosed, scrolled: !!scrolled, 'vote-style-beta-510': preferencesStore.user.useAlternativeStyling }, pollStore.type]">
		<HeaderBar class="area__header">
			<template #title>
				{{ pollStore.configuration.title }} scrolled: {{ scrolled }}
			</template>

			<template #right>
				<PollHeaderButtons />
			</template>

			<PollInfoLine />
		</HeaderBar>

		<div :id="voteMainId" class="vote_main">
			<VoteInfoCards />

			<div v-if="pollStore.configuration.description" class="area__description">
				<MarkUpDescription />
			</div>

			<div class="area__main" :class="pollStore.viewMode">
				<VoteTable v-show="optionsStore.list.length" />

				<NcEmptyContent v-if="!optionsStore.list.length"
					v-bind="emptyContentProps">
					<template #icon>
						<TextPollIcon v-if="pollStore.type === PollType.Text" />
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
