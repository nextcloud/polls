<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

import { showError } from '@nextcloud/dialogs'
import { t, n } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import HeaderBar from '../components/Base/modules/HeaderBar.vue'
import IntersectionObserver from '../components/Base/modules/IntersectionObserver.vue'
import PollsAppIcon from '../components/AppIcons/PollsAppIcon.vue'
import PollItem from '../components/PollList/PollItem.vue'
import PollListSort from '../components/PollList/PollListSort.vue'
import PollItemActions from '../components/PollList/PollItemActions.vue'
import ActionAddPoll from '../components/Actions/modules/ActionAddPoll.vue'
import ActionToggleSidebar from '../components/Actions/modules/ActionToggleSidebar.vue'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'

import { usePreferencesStore } from '../stores/preferences'
import { useSessionStore } from '../stores/session'
import { usePollsStore } from '../stores/polls'
import { usePollGroupsStore } from '../stores/pollGroups'

import type { FilterType } from '../stores/polls.types'

const pollsStore = usePollsStore()
const pollGroupsStore = usePollGroupsStore()
const preferencesStore = usePreferencesStore()
const sessionStore = useSessionStore()
const router = useRouter()
const route = useRoute()

const title = computed(() => {
	if (route.name === 'group') {
		return (
			pollGroupsStore.currentPollGroup?.titleExt
			|| pollGroupsStore.currentPollGroup?.name
			|| ''
		)
	}
	return pollsStore.categories[route.params.type as FilterType].titleExt
})

const showMore = computed(
	() =>
		pollsStore.chunkedList.length < pollsStore.pollsFilteredSorted.length
		&& pollsStore.meta.status !== 'loading',
)

const countLoadedPolls = computed(() =>
	Math.min(pollsStore.chunkedList.length, pollsStore.pollsFilteredSorted.length),
)

const infoLoaded = computed(() =>
	n(
		'polls',
		'{loadedPolls} of {countPolls} poll loaded.',
		'{loadedPolls} of {countPolls} polls loaded.',
		pollsStore.pollsFilteredSorted.length,
		{
			loadedPolls: countLoadedPolls.value,
			countPolls: pollsStore.pollsFilteredSorted.length,
		},
	),
)

const description = computed(() => {
	if (route.name === 'group') {
		return pollGroupsStore.currentPollGroup?.description || ''
	}

	return pollsStore.categories[route.params.type as FilterType].description
})

const emptyPollListnoPolls = computed(
	() => pollsStore.pollsFilteredSorted.length < 1,
)

const loadingOverlayProps = {
	name: t('polls', 'Loading overview…'),
	teleportTo: '#content-vue',
	loadingTexts: [
		t('polls', 'Fetching polls…'),
		t('polls', 'Checking access…'),
		t('polls', 'Almost ready…'),
		t('polls', 'Do not go away…'),
		t('polls', 'Please be patient…'),
	],
}

const emptyContentProps = computed(() => ({
	name: t('polls', 'No polls found for this category'),
	description: t('polls', 'Add one or change category!'),
}))

/**
 *
 * @param pollId - The poll id to clone
 */
function gotoPoll(pollId: number) {
	router.push({
		name: 'vote',
		params: { id: pollId },
	})
}

/**
 *
 */
async function loadMore() {
	try {
		pollsStore.addChunk()
	} catch {
		showError(t('polls', 'Error loading more polls'))
	}
}

onMounted(() => {
	pollsStore.load(false)
})
</script>

<template>
	<NcAppContent class="poll-list">
		<HeaderBar>
			<template #title>
				{{ title }}
			</template>
			{{ description }}
			<template #right>
				<ActionAddPoll v-if="preferencesStore.user.useNewPollInPollist" />
				<PollListSort />
				<ActionToggleSidebar
					v-if="
						pollGroupsStore.currentPollGroup?.owner.id
						=== sessionStore.currentUser.id
					" />
			</template>
		</HeaderBar>

		<div class="area__main">
			<TransitionGroup
				v-if="!emptyPollListnoPolls"
				tag="div"
				name="list"
				class="poll-list__list">
				<PollItem
					v-for="poll in pollsStore.chunkedList"
					:key="poll.id"
					:poll="poll"
					@goto-poll="gotoPoll(poll.id)">
					<template #actions>
						<PollItemActions
							v-if="
								poll.permissions.edit
								|| sessionStore.appPermissions.pollCreation
							"
							:key="`actions-${poll.id}`"
							:poll="poll" />
					</template>
				</PollItem>
			</TransitionGroup>

			<IntersectionObserver
				v-if="showMore"
				key="observer"
				class="observer_section"
				@visible="loadMore">
				<div class="clickable_load_more" @click="loadMore">
					{{ infoLoaded }}
					{{ t('polls', 'Click here to load more') }}
				</div>
			</IntersectionObserver>

			<NcEmptyContent v-if="emptyPollListnoPolls" v-bind="emptyContentProps">
				<template #icon>
					<PollsAppIcon />
				</template>
			</NcEmptyContent>
		</div>
		<LoadingOverlay
			:show="pollsStore.meta.status === 'loading'"
			v-bind="loadingOverlayProps" />
	</NcAppContent>
</template>

<style lang="scss">
.poll-list__list {
	width: 100%;
	display: flex;
	flex-direction: column;
	overflow: scroll;
	padding-bottom: 14px;
}

.observer_section {
	display: flex;
	justify-content: center;
	align-items: center;
	padding: 14px 0;
}

.clickable_load_more {
	cursor: pointer;
	font-weight: bold;
}
</style>
