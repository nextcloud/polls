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
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'

import { Logger } from '../helpers/index.ts'
import { HeaderBar, IntersectionObserver } from '../components/Base/index.js'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import PollItem from '../components/PollList/PollItem.vue'
import { usePollsStore } from '../stores/polls.ts'
import { useSessionStore } from '../stores/session.ts'
import PollListSort from '../components/PollList/PollListSort.vue'

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()
const router = useRouter()
const route = useRoute()

const title = computed(
	() =>
		pollsStore.categories.find((category) => category.id === route.params.type)
			?.titleExt,
)
const showMore = computed(
	() =>
		pollsStore.chunkedList.length < pollsStore.pollsFilteredSorted.length &&
		pollsStore.meta.status !== 'loading',
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
const description = computed(
	() =>
		pollsStore.categories.find((category) => category.id === route.params.type)
			?.description,
)
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
	router.push({ name: 'vote', params: { id: pollId } })
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

/**
 *
 * @param pollId - The poll id to clone
 */
async function toggleArchive(pollId: number) {
	try {
		await pollsStore.toggleArchive({ pollId })
	} catch {
		showError(t('polls', 'Error archiving/restoring poll.'))
	}
}

/**
 *
 * @param pollId - The poll id to delete
 */
async function deletePoll(pollId: number) {
	try {
		await pollsStore.delete({ pollId })
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}

/**
 *
 * @param pollId - The poll id to clone
 */
async function clonePoll(pollId: number) {
	try {
		await pollsStore.clone({ pollId })
	} catch {
		showError(t('polls', 'Error cloning poll.'))
	}
}
</script>

<template>
	<NcAppContent class="poll-list">
		<HeaderBar class="area__header">
			<template #title>
				{{ title }}
			</template>
			{{ description }}
			<template #right>
				<PollListSort />
			</template>

		</HeaderBar>

		<div class="area__main">
			<TransitionGroup v-if="!emptyPollListnoPolls" tag="div" name="list" class="poll-list__list">

				<PollItem
					v-for="poll in pollsStore.chunkedList"
					:key="poll.id"
					:poll="poll"
					@goto-poll="gotoPoll(poll.id)">
					<template #actions>
						<NcActions force-menu>
							<NcActionButton
								v-if="sessionStore.appPermissions.pollCreation"
								:name="t('polls', 'Clone poll')"
								:aria-label="t('polls', 'Clone poll')"
								close-after-click
								@click="clonePoll(poll.id)">
								<template #icon>
									<ClonePollIcon />
								</template>
							</NcActionButton>

							<NcActionButton
								v-if="
									poll.permissions.edit &&
									!poll.status.isDeleted
								"
								:name="t('polls', 'Archive poll')"
								:aria-label="t('polls', 'Archive poll')"
								close-after-click
								@click="toggleArchive(poll.id)">
								<template #icon>
									<ArchivePollIcon />
								</template>
							</NcActionButton>

							<NcActionButton
								v-if="
									poll.permissions.edit &&
									poll.status.isDeleted
								"
								:name="t('polls', 'Restore poll')"
								:aria-label="t('polls', 'Restore poll')"
								close-after-click
								@click="toggleArchive(poll.id)">
								<template #icon>
									<RestorePollIcon />
								</template>
							</NcActionButton>

							<NcActionButton
								v-if="
									poll.permissions.edit &&
									poll.status.isDeleted
								"
								class="danger"
								:name="t('polls', 'Delete poll')"
								:aria-label="t('polls', 'Delete poll')"
								close-after-click
								@click="deletePoll(poll.id)">
								<template #icon>
									<DeletePollIcon />
								</template>
							</NcActionButton>
						</NcActions>
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
