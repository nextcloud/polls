<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

import { showError } from '@nextcloud/dialogs'
import { t, n } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

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
	() => pollsStore.hasMore && pollsStore.list.status !== 'loading',
)

// after an error the observer would fire on every remount and retry endlessly
const autoLoadMore = computed(
	() => showMore.value && pollsStore.list.status !== 'error',
)

const infoLoaded = computed(() =>
	n(
		'polls',
		'{loadedPolls} of {countPolls} poll loaded.',
		'{loadedPolls} of {countPolls} polls loaded.',
		pollsStore.list.total,
		{
			loadedPolls: pollsStore.list.polls.length,
			countPolls: pollsStore.list.total,
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
	() =>
		pollsStore.list.status !== 'loading' && pollsStore.list.polls.length < 1,
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

const loadingMore = ref(false)

/**
 * Append the next page
 */
async function loadMore() {
	loadingMore.value = true
	try {
		await pollsStore.loadMore()
	} catch {
		showError(t('polls', 'Error loading more polls'))
	} finally {
		loadingMore.value = false
	}
}

/**
 * Load the first page of the current category or poll group
 */
async function loadList() {
	// the route watcher also fires, when leaving the list
	if (!['list', 'group'].includes(sessionStore.route.name as string)) {
		return
	}
	try {
		await pollsStore.loadList()
	} catch {
		showError(t('polls', 'Error loading polls'))
	}
}

// the store reads the route from the session store
watch(() => sessionStore.route.currentRoute, loadList)
watch(() => [pollsStore.sort.by, pollsStore.sort.reverse], loadList)

onMounted(loadList)
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
					v-for="poll in pollsStore.list.polls"
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

			<div
				v-if="loadingMore"
				class="observer_section load_more_loading"
				role="status">
				<NcLoadingIcon :size="32" />
				<span>{{ t('polls', 'Loading more polls…') }}</span>
			</div>

			<IntersectionObserver
				v-else-if="autoLoadMore"
				key="observer"
				class="observer_section"
				@visible="loadMore">
				<div class="clickable_load_more" @click="loadMore">
					{{ infoLoaded }}
					{{ t('polls', 'Click here to load more') }}
				</div>
			</IntersectionObserver>

			<div v-else-if="showMore" class="observer_section">
				<div class="clickable_load_more" @click="loadMore">
					{{ infoLoaded }}
					{{ t('polls', 'Click here to load more') }}
				</div>
			</div>

			<NcEmptyContent v-if="emptyPollListnoPolls" v-bind="emptyContentProps">
				<template #icon>
					<PollsAppIcon />
				</template>
			</NcEmptyContent>
		</div>
		<LoadingOverlay
			:show="pollsStore.list.status === 'loading' && !loadingMore"
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

.load_more_loading {
	gap: 8px;
	color: var(--color-text-maxcontrast);
}

.clickable_load_more {
	cursor: pointer;
	font-weight: bold;
}
</style>
