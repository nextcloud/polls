<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent class="poll-list">
		<HeaderBar class="area__header">
			<template #title>
				{{ title }}
			</template>
			{{ description }}
		</HeaderBar>

		<div class="area__main">
			<TransitionGroup tag="div" name="list" class="poll-list__list">
				<PollItem key="0" :header="true" @sort-list="setSortColumn($event)" />

				<template v-if="!emptyPollListnoPolls">
					<PollItem v-for="(poll) in pollList"
						:key="poll.id"
						:poll="poll"
						@goto-poll="gotoPoll(poll.id)"
						@load-poll="loadPoll(poll.id)">
						<template #actions>
							<NcActions force-menu>
								<NcActionButton v-if="isPollCreationAllowed"
									:name="t('polls', 'Clone poll')"
									close-after-click
									@click="clonePoll(poll.id)">
									<template #icon>
										<ClonePollIcon />
									</template>
								</NcActionButton>

								<NcActionButton v-if="poll.permissions.edit && !poll.deleted"
									:name="t('polls', 'Archive poll')"
									close-after-click
									@click="toggleArchive(poll.id)">
									<template #icon>
										<ArchivePollIcon />
									</template>
								</NcActionButton>

								<NcActionButton v-if="poll.permissions.edit && poll.deleted"
									:name="t('polls', 'Restore poll')"
									close-after-click
									@click="toggleArchive(poll.id)">
									<template #icon>
										<RestorePollIcon />
									</template>
								</NcActionButton>

								<NcActionButton v-if="poll.permissions.edit && poll.deleted"
									class="danger"
									:name="t('polls', 'Delete poll')"
									close-after-click
									@click="deletePoll(poll.id)">
									<template #icon>
										<DeletePollIcon />
									</template>
								</NcActionButton>
							</NcActions>
						</template>
					</PollItem>
				</template>
			</TransitionGroup>

			<IntersectionObserver v-if="showMore"
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
					<NcLoadingIcon v-if="isLoading" :size="64" />
					<PollsAppIcon v-else />
				</template>
			</NcEmptyContent>
		</div>
	</NcAppContent>
</template>

<script>
import { mapGetters, mapState, mapActions, mapMutations } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcAppContent, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { HeaderBar, IntersectionObserver } from '../components/Base/index.js'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import PollItem from '../components/PollList/PollItem.vue'

export default {
	name: 'PollList',

	components: {
		ArchivePollIcon,
		ClonePollIcon,
		DeletePollIcon,
		HeaderBar,
		IntersectionObserver,
		NcAppContent,
		NcActions,
		NcActionButton,
		NcEmptyContent,
		NcLoadingIcon,
		RestorePollIcon,
		PollsAppIcon,
		PollItem,
	},

	computed: {
		...mapState({
			pollCategories: (state) => state.polls.categories,
			isPollCreationAllowed: (state) => state.polls.meta.permissions.isPollCreationAllowed,
			isLoading: (state) => state.polls.pollsLoading,
		}),

		...mapGetters({
			filteredPolls: 'polls/filtered',
			countPolls: 'polls/count',
			loadedPolls: 'polls/loaded',
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
				description: t('polls', 'Add one or change category!'),
			}
		},

		title() {
			return this.pollCategories.find((category) => (category.id === this.$route.params.type))?.titleExt
		},

		showMore() {
			return this.loadedPolls < this.countAvailablePolls && !this.isLoading
		},
		countAvailablePolls() {
			return this.countPolls
		},

		countLoadedPolls() {
			return Math.min(this.loadedPolls, this.countPolls)
		},

		infoLoaded() {
			return n('polls', '{loadedPolls} of {countPolls} poll loaded.', '{loadedPolls} of {countPolls} polls loaded.', this.countAvailablePolls,
				{ loadedPolls: this.countLoadedPolls, countPolls: this.countAvailablePolls })
		},

		description() {
			return this.pollCategories.find((category) => (category.id === this.$route.params.type))?.description
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.title}`
		},

		pollList() {
			return this.filteredPolls
		},

		emptyPollListnoPolls() {
			return this.pollList.length < 1
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
		...mapActions({
			setSortColumn: 'polls/setSort',
		}),
		...mapMutations({
			addChunk: 'polls/addChunk',
		}),

		gotoPoll(pollId) {
			this.$router
				.push({ name: 'vote', params: { id: pollId } })
		},

		async loadMore() {
			try {
				await this.addChunk()
			} catch {
				showError(t('polls', 'Error loading more polls'))
			}
		},

		async loadPoll(pollId) {
			try {
				await this.$store.dispatch({ type: 'poll/get', pollId })
			} catch {
				showError(t('polls', 'Error loading poll'))
			}
		},

		refreshView() {
			window.document.title = `${t('polls', 'Polls')} - ${this.title}`
		},

		async toggleArchive(pollId) {
			try {
				await this.$store.dispatch('poll/toggleArchive', { pollId })
			} catch {
				showError(t('polls', 'Error archiving/restoring poll.'))
			}
		},

		async deletePoll(pollId) {
			try {
				await this.$store.dispatch('poll/delete', { pollId })
			} catch {
				showError(t('polls', 'Error deleting poll.'))
			}
		},

		async clonePoll(pollId) {
			try {
				await this.$store.dispatch('poll/clone', { pollId })
			} catch {
				showError(t('polls', 'Error cloning poll.'))
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
