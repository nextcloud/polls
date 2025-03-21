<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { sortBy } from 'lodash'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'

import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

import PollItem from '../components/PollList/PollItem.vue'
import { HeaderBar } from '../components/Base/index.js'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import { usePollsAdminStore } from '../stores/pollsAdmin.ts'
import { SortType, Poll, StatusResults } from '../Types/index.ts'

const pollsAdminStore = usePollsAdminStore()

const sort = ref(SortType.Created)
const reverse = ref(true)
const takeOverModal = ref(false)
const deleteModal = ref(false)
const currentPoll = ref(null)

const emptyContent = computed(() => {
	if (pollsAdminStore.meta.status === StatusResults.Loading) {
		return {
			name: t('polls', 'Loading polls…'),
			description: '',
		}
	}

	return {
		name: t('polls', 'No polls found for this category'),
		description: '',
	}
})

const title = computed(() => t('polls', 'Administration'))

const sortedList = computed<Poll[]>(() => {
	if (reverse.value) {
		return sortBy(pollsAdminStore.list, sort.value).reverse()
	}
	return sortBy(pollsAdminStore.list, sort.value)
})

const isEmptyPollList = computed(() => sortedList.value.length < 1)

function confirmTakeOver(poll: Poll) {
	currentPoll.value = poll
	takeOverModal.value = true
}

function confirmDelete(poll: Poll) {
	currentPoll.value = poll
	deleteModal.value = true
}

async function toggleArchive(pollId: number) {
	try {
		await pollsAdminStore.toggleArchive({ pollId })
	} catch {
		showError(t('polls', 'Error archiving/restoring poll.'))
	}
}

async function deletePoll() {
	try {
		await pollsAdminStore.delete({ pollId: currentPoll.value.id })
		deleteModal.value = false
	} catch {
		showError(t('polls', 'Error deleting poll.'))
		deleteModal.value = false
	}
}

async function takeOverPoll() {
	try {
		await pollsAdminStore.takeOver({ pollId: currentPoll.value.id })
		takeOverModal.value = false
	} catch {
		showError(t('polls', 'Error overtaking poll.'))
		takeOverModal.value = false
	}
}

function loadPolls() {
	try {
		pollsAdminStore.load()
	} catch {
		showError(t('polls', 'Error loading polls list for admins'))
	}
}

function refreshView() {
	window.document.title = `${t('polls', 'Polls')} - ${title.value}`
}

onMounted(() => {
	loadPolls()
	refreshView()
})
</script>

<template>
	<NcAppContent class="poll-list">
		<HeaderBar class="area__header">
			<template #title>
				{{ t('polls', 'Administrative poll management') }}
			</template>
			{{
				t(
					'polls',
					'Manage polls of other accounts. You can take over the ownership or delete polls.',
				)
			}}
		</HeaderBar>

		<div class="area__main">
			<TransitionGroup tag="div" name="list" class="poll-list__list">
				<PollItem
					key="0"
					:header="true"
					:sort="sort"
					:reverse="reverse"
					@sort-list="pollsAdminStore.setSort($event)" />

				<template v-if="!isEmptyPollList">
					<PollItem
						v-for="poll in sortedList"
						:key="poll.id"
						:poll="poll"
						no-link>
						<template #actions>
							<NcActions :force-menu="true">
								<NcActionButton
									:name="t('polls', 'Take over')"
									:aria-label="t('polls', 'Take over')"
									close-after-click
									@click="confirmTakeOver(poll)">
									<template #icon>
										<PlusIcon />
									</template>
								</NcActionButton>

								<NcActionButton
									:name="
										poll.status.isDeleted
											? t('polls', 'Restore poll')
											: t('polls', 'Archive poll')
									"
									:aria-label="
										poll.status.isDeleted
											? t('polls', 'Restore poll')
											: t('polls', 'Archive poll')
									"
									close-after-click
									@click="toggleArchive(poll.id)">
									<template #icon>
										<RestorePollIcon
											v-if="poll.status.isDeleted" />
										<ArchivePollIcon v-else />
									</template>
								</NcActionButton>

								<NcActionButton
									class="danger"
									:name="t('polls', 'Delete poll')"
									:aria-label="t('polls', 'Delete poll')"
									close-after-click
									@click="confirmDelete(poll)">
									<template #icon>
										<DeleteIcon />
									</template>
								</NcActionButton>
							</NcActions>
						</template>
					</PollItem>
				</template>
			</TransitionGroup>

			<NcEmptyContent v-if="isEmptyPollList" v-bind="emptyContent">
				<template #icon>
					<NcLoadingIcon
						v-if="pollsAdminStore.meta.status === StatusResults.Loading"
						:size="64" />
					<PollsAppIcon v-else />
				</template>
			</NcEmptyContent>
		</div>

		<NcModal v-if="takeOverModal" size="small" @close="takeOverModal = false">
			<div class="modal__content">
				<h2>{{ t('polls', 'Do you want to take over this poll?') }}</h2>
				<div>
					{{
						t('polls', '{username} will get notified.', {
							username: currentPoll.owner.displayName,
						})
					}}
				</div>
				<div class="modal__buttons">
					<NcButton @click="takeOverModal = false">
						<template #default>
							{{ t('polls', 'No') }}
						</template>
					</NcButton>

					<NcButton :type="ButtonType.Primary" @click="takeOverPoll()">
						<template #default>
							{{ t('polls', 'Yes') }}
						</template>
					</NcButton>
				</div>
			</div>
		</NcModal>

		<NcModal v-if="deleteModal" size="small" @close="deleteModal = false">
			<div class="modal__content">
				<h2>{{ t('polls', 'Do you want to delete this poll?') }}</h2>
				<div>
					{{ t('polls', 'This action cannot be reverted.') }}
					{{
						t('polls', '{username} will get notified.', {
							username: currentPoll.owner.displayName,
						})
					}}
				</div>
				<div class="modal__buttons">
					<NcButton @click="deleteModal = false">
						<template #default>
							{{ t('polls', 'No') }}
						</template>
					</NcButton>

					<NcButton :type="ButtonType.Primary" @click="deletePoll()">
						<template #default>
							{{ t('polls', 'Yes') }}
						</template>
					</NcButton>
				</div>
			</div>
		</NcModal>
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
</style>
../Types/index.ts
