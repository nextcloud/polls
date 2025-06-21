<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showError } from '@nextcloud/dialogs'
import { t, n } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

import { Logger } from '../helpers/index.ts'
import { HeaderBar, IntersectionObserver } from '../components/Base/index.ts'
import { PollsAppIcon } from '../components/AppIcons/index.ts'
import PollItem from '../components/PollList/PollItem.vue'
import { FilterType, usePollsStore } from '../stores/polls.ts'
import PollListSort from '../components/PollList/PollListSort.vue'
import PollItemActions from '../components/PollList/PollItemActions.vue'
import ActionAddPoll from '../components/Actions/modules/ActionAddPoll.vue'
import { usePreferencesStore } from '../stores/preferences.ts'
import { useSessionStore } from '../stores/session.ts'
import ActionEditGroup from '../components/Actions/modules/ActionEditGroup.vue'

const pollsStore = usePollsStore()
const preferencesStore = usePreferencesStore()
const sessionStore = useSessionStore()
const router = useRouter()
const route = useRoute()

const editable = computed(
	() =>
		route.name === 'group'
		&& sessionStore.currentUser.id === pollsStore.currentGroup?.owner.id,
)

const title = computed(() => {
	if (route.name === 'group') {
		return (
			pollsStore.currentGroup?.titleExt
			|| pollsStore.currentGroup?.title
			|| t('polls', 'Group without title')
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
		return (
			pollsStore.currentGroup?.description
			|| t('polls', 'Group without description')
		)
	}

	return pollsStore.categories[route.params.type as FilterType].description
})

const emptyPollListnoPolls = computed(
	() => pollsStore.pollsFilteredSorted.length < 1,
)

const windowTitle = computed(() => `${t('polls', 'Polls')} - ${title.value}`)

const emptyContent = computed(() => {
	if (pollsStore.meta.status === 'loading') {
		return {
			name: t('polls', 'Loading polls…'),
			description: '',
		}
	}

	return {
		name: t('polls', 'No polls found for this category'),
		description: t('polls', 'Add one or change category!'),
	}
})

onMounted(() => {
	Logger.debug('Loading polls onMounted')
	pollsStore.load()
	refreshView()
})

watch(
	() => route.params.id,
	() => {
		Logger.debug('Loading polls on watch')
		pollsStore.load()
		refreshView()
	},
)

/**
 *
 */
function refreshView() {
	window.document.title = windowTitle.value
}

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
</script>

<template>
	<NcAppContent class="poll-list">
		<HeaderBar>
			<template #title>
				{{ title }}
			</template>
			{{ description }}
			<template #right>
				<ActionEditGroup v-if="editable" />
				<ActionAddPoll v-if="preferencesStore.user.useNewPollInPollist" />
				<PollListSort />
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

			<NcEmptyContent v-if="emptyPollListnoPolls" v-bind="emptyContent">
				<template #icon>
					<NcLoadingIcon
						v-if="pollsStore.meta.status === 'loading'"
						:size="64" />
					<PollsAppIcon v-else />
				</template>
			</NcEmptyContent>
		</div>
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
