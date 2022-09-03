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
			<NcEmptyContent v-if="noPolls">
				<template #icon>
					<PollsAppIcon />
				</template>
				<template #desc>
					{{ t('polls', 'Add one or change category!') }}
				</template>
				{{ t('polls', 'No polls found for this category') }}
			</NcEmptyContent>

			<transition-group v-else
				name="list"
				tag="div"
				class="poll-list__list">
				<PollItem key="0"
					:header="true"
					@sort-list="setSortColumn($event)" />

				<PollItem v-for="(poll) in pollList"
					:key="poll.id"
					:poll="poll"
					@goto-poll="gotoPoll(poll.id)"
					@load-poll="loadPoll(poll.id)">
					<template #actions>
						<NcActions force-menu>
							<NcActionButton v-if="isPollCreationAllowed"
								:close-after-click="true"
								@click="clonePoll(poll.id)">
								<template #icon>
									<ClonePollIcon />
								</template>
								{{ t('polls', 'Clone poll') }}
							</NcActionButton>

							<NcActionButton v-if="poll.allowEdit && !poll.deleted"
								:close-after-click="true"
								@click="toggleArchive(poll.id)">
								<template #icon>
									<ArchivePollIcon />
								</template>
								{{ t('polls', 'Archive poll') }}
							</NcActionButton>

							<NcActionButton v-if="poll.allowEdit && poll.deleted"
								:close-after-click="true"
								@click="toggleArchive(poll.id)">
								<template #icon>
									<RestorePollIcon />
								</template>
								{{ t('polls', 'Restore poll') }}
							</NcActionButton>

							<NcActionButton v-if="poll.allowEdit && poll.deleted"
								class="danger"
								:close-after-click="true"
								@click="deletePoll(poll.id)">
								<template #icon>
									<DeletePollIcon />
								</template>
								{{ t('polls', 'Delete poll') }}
							</NcActionButton>
						</NcActions>
					</template>
				</PollItem>
			</transition-group>
		</div>
		<LoadingOverlay v-if="isLoading" />
	</NcAppContent>
</template>

<script>
import { mapGetters, mapState, mapActions } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { NcActions, NcActionButton, NcAppContent, NcEmptyContent } from '@nextcloud/vue'
import HeaderBar from '../components/Base/HeaderBar.vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import ClonePollIcon from 'vue-material-design-icons/ContentCopy.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import PollsAppIcon from '../components/AppIcons/PollsAppIcon.vue'

export default {
	name: 'PollList',

	components: {
		NcAppContent,
		NcActions,
		NcActionButton,
		NcEmptyContent,
		HeaderBar,
		DeletePollIcon,
		ClonePollIcon,
		ArchivePollIcon,
		RestorePollIcon,
		PollsAppIcon,
		LoadingOverlay: () => import('../components/Base/LoadingOverlay.vue'),
		PollItem: () => import('../components/PollList/PollItem.vue'),
	},

	data() {
		return {
			isLoading: false,
		}
	},

	computed: {
		...mapState({
			pollCategories: (state) => state.polls.categories,
			isPollCreationAllowed: (state) => state.polls.isPollCreationAllowed,
		}),

		...mapGetters({
			filteredPolls: 'polls/filtered',
		}),

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

		noPolls() {
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
				emit('polls:sidebar:toggle', { open: true })
			} catch {
				showError(t('polls', 'Error loading poll'))
			}
		},

		refreshView() {
			window.document.title = `${t('polls', 'Polls')} - ${this.title}`
			if (!this.filteredPolls(this.$route.params.type).find((poll) => poll.id === this.$store.state.poll.id)) {
				emit('polls:sidebar:toggle', { open: false })
			}

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
