<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent class="poll-list">
		<HeaderBar class="area__header">
			<template #title>
				{{ t('polls', 'Administrative poll management') }}
			</template>
			{{ t('polls', 'Manage polls of other accounts. You can take over the ownership or delete polls.') }}
		</HeaderBar>

		<div class="area__main">
			<TransitionGroup is="div" name="list" class="poll-list__list">
				<PollItem key="0"
					:header="true"
					:sort="sort"
					:reverse="reverse"
					@sort-list="setSort($event)" />

				<template v-if="!isEmptyPollList">
					<PollItem v-for="(poll) in sortedList"
						:key="poll.id"
						:poll="poll"
						no-link>
						<template #actions>
							<NcActions :force-menu="true">
								<NcActionButton :name="t('polls', 'Take over')"
									:aria-label="t('polls', 'Take over')"
									close-after-click
									@click="confirmTakeOver(poll.id, poll.owner)">
									<template #icon>
										<PlusIcon />
									</template>
								</NcActionButton>

								<NcActionButton :name="poll.status.deleted ? t('polls', 'Restore poll') : t('polls', 'Archive poll')"
									:aria-label="poll.status.deleted ? t('polls', 'Restore poll') : t('polls', 'Archive poll')"
									close-after-click
									@click="toggleArchive(poll.id)">
									<template #icon>
										<RestorePollIcon v-if="poll.status.deleted" />
										<ArchivePollIcon v-else />
									</template>
								</NcActionButton>

								<NcActionButton class="danger"
									:name="t('polls', 'Delete poll')"
									:aria-label="t('polls', 'Delete poll')"
									close-after-click
									@click="confirmDelete(poll.id, poll.owner)">
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
					<NcLoadingIcon v-if="isLoading" :size="64" />
					<PollsAppIcon v-else />
				</template>
			</NcEmptyContent>
		</div>

		<NcModal v-if="takeOverModal" size="small" @close="takeOverModal = false">
			<div class="modal__content">
				<h2>{{ t('polls', 'Do you want to take over this poll?') }}</h2>
				<div>{{ t('polls', '{username} will get notified.', {username: currentPoll.owner.displayName}) }}</div>
				<div class="modal__buttons">
					<NcButton @click="takeOverModal = false">
						<template #default>
							{{ t('polls', 'No') }}
						</template>
					</NcButton>

					<NcButton variant="primary" @click="takeOverPoll()">
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
					{{ t('polls', '{username} will get notified.', {username: currentPoll.owner.displayName}) }}
				</div>
				<div class="modal__buttons">
					<NcButton @click="deleteModal = false">
						<template #default>
							{{ t('polls', 'No') }}
						</template>
					</NcButton>

					<NcButton variant="primary" @click="deletePoll()">
						<template #default>
							{{ t('polls', 'Yes') }}
						</template>
					</NcButton>
				</div>
			</div>
		</NcModal>
	</NcAppContent>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcAppContent, NcButton, NcEmptyContent, NcLoadingIcon, NcModal } from '@nextcloud/vue'
import { sortBy } from 'lodash'
import { HeaderBar } from '../components/Base/index.js'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import PollItem from '../components/PollList/PollItem.vue'

export default {
	name: 'Administration',

	components: {
		ArchivePollIcon,
		DeleteIcon,
		HeaderBar,
		NcAppContent,
		NcActions,
		NcActionButton,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcModal,
		PlusIcon,
		PollsAppIcon,
		RestorePollIcon,
		PollItem,
	},

	data() {
		return {
			sort: 'created',
			reverse: true,
			takeOverModal: false,
			currentPoll: {
				owner: '',
				pollId: 0,
			},
			deleteModal: false,
		}
	},

	computed: {
		...mapState({
			isLoading: (state) => state.polls.status.loading,
		}),

		...mapGetters({
			filteredPolls: 'pollsAdmin/filtered',
		}),

		emptyContent() {
			if (this.isLoading) {
				return {
					name: t('polls', 'Loading polls…'),
					description: '',
				}
			}

			return {
				name: t('polls', 'No polls found for this category'),
				description: '',
			}
		},

		title() {
			return t('polls', 'Administration')
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.title}`
		},

		sortedList() {
			if (this.reverse) {
				return sortBy(this.filteredPolls(this.$route.params.type), this.sort).reverse()
			}
			return sortBy(this.filteredPolls(this.$route.params.type), this.sort)
		},

		isEmptyPollList() {
			return this.sortedList.length < 1
		},

	},

	watch: {
		$route() {
			this.refreshView()
		},
	},

	mounted() {
		this.refreshView()
	},

	methods: {
		confirmTakeOver(pollId, currentOwner) {
			this.currentPoll.pollId = pollId
			this.currentPoll.owner = currentOwner
			this.takeOverModal = true
		},

		confirmDelete(pollId, currentOwner) {
			this.currentPoll.pollId = pollId
			this.currentPoll.owner = currentOwner
			this.deleteModal = true
		},

		async toggleArchive(pollId) {
			try {
				await this.$store.dispatch('poll/toggleArchive', { pollId })
			} catch {
				showError(t('polls', 'Error archiving/restoring poll.'))
			}
		},

		async deletePoll() {
			try {
				await this.$store.dispatch('poll/delete', { pollId: this.currentPoll.pollId })
				this.deleteModal = false
			} catch {
				showError(t('polls', 'Error deleting poll.'))
				this.deleteModal = false
			}
		},

		async takeOverPoll() {
			try {
				await this.$store.dispatch('pollsAdmin/takeOver', { pollId: this.currentPoll.pollId })
				this.takeOverModal = false
			} catch {
				showError(t('polls', 'Error overtaking poll.'))
				this.takeOverModal = false
			}
		},

		refreshView() {
			window.document.title = `${t('polls', 'Polls')} - ${this.title}`
		},

		setSort(payload) {
			if (this.sort === payload.sort) {
				this.reverse = !this.reverse
			} else {
				this.sort = payload.sort
				this.reverse = true
			}
		},

	},
}
</script>

<style lang="scss">
.poll-list__list {
	width: 100%;
	display: flex;
	flex-direction: column;
	overflow: scroll;
	padding-bottom: 14px;
}
</style>
