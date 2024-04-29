<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
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
import { mapGetters, mapState, mapActions } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcAppContent, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { HeaderBar } from '../components/Base/index.js'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import { PollsAppIcon } from '../components/AppIcons/index.js'

export default {
	name: 'PollList',

	components: {
		ArchivePollIcon,
		ClonePollIcon,
		DeletePollIcon,
		HeaderBar,
		NcAppContent,
		NcActions,
		NcActionButton,
		NcEmptyContent,
		NcLoadingIcon,
		RestorePollIcon,
		PollsAppIcon,
		PollItem: () => import('../components/PollList/PollItem.vue'),
	},

	computed: {
		...mapState({
			pollCategories: (state) => state.polls.categories,
			isPollCreationAllowed: (state) => state.polls.meta.permissions.isPollCreationAllowed,
			isLoading: (state) => state.polls.pollsLoading,
		}),

		...mapGetters({
			filteredPolls: 'polls/filtered',
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

		description() {
			return this.pollCategories.find((category) => (category.id === this.$route.params.type))?.description
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.title}`
		},

		pollList() {
			return this.filteredPolls(this.$route.params.type)
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

		gotoPoll(pollId) {
			this.$router
				.push({ name: 'vote', params: { id: pollId } })
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
</style>
