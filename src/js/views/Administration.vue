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
	<AppContent class="poll-list">
		<HeaderBar class="area__header">
			<template #title>
				{{ t('polls', 'Administrative poll management') }}
			</template>
			{{ t('polls', 'Manage polls of other users. You can take over the ownership or delete polls.') }}
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
					:sort="sort"
					:reverse="reverse"
					@sort-list="setSort($event)" />

				<PollItem v-for="(poll) in sortedList"
					:key="poll.id"
					:poll="poll"
					no-link>
					<template #actions>
						<NcActions :force-menu="true">
							<NcActionButton icon="icon-add"
								:close-after-click="true"
								@click="confirmTakeOver(poll.id, poll.owner)">
								{{ t('polls', 'Take over') }}
							</NcActionButton>

							<NcActionButton :icon="poll.deleted ? 'icon-history' : 'icon-category-app-bundles'"
								:close-after-click="true"
								@click="toggleArchive(poll.id)">
								{{ poll.deleted ? t('polls', 'Restore poll') : t('polls', 'Archive poll') }}
							</NcActionButton>

							<NcActionButton icon="icon-delete"
								class="danger"
								:close-after-click="true"
								@click="confirmDelete(poll.id, poll.owner)">
								{{ t('polls', 'Delete poll') }}
							</NcActionButton>
						</NcActions>
					</template>
				</PollItem>
			</transition-group>
		</div>

		<LoadingOverlay v-if="isLoading" />

		<NcModal v-if="takeOverModal" size="small" @close="takeOverModal = false">
			<div class="modal__content">
				<h2>{{ t('polls', 'Do you want to take over this poll?') }}</h2>
				<div>{{ t('polls', '{username} will get notified.', {username: currentPoll.owner.displayName}) }}</div>
				<div class="modal__buttons">
					<NcButton @click="takeOverModal = false">
						{{ t('polls', 'No') }}
					</NcButton>

					<NcButton type="primary" @click="takeOverPoll()">
						{{ t('polls', 'Yes') }}
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
						{{ t('polls', 'No') }}
					</NcButton>

					<NcButton type="primary" @click="deletePoll()">
						{{ t('polls', 'Yes') }}
					</NcButton>
				</div>
			</div>
		</NcModal>
	</AppContent>
</template>

<script>
import { mapGetters } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { NcActions, NcActionButton, AppContent, NcButton, NcEmptyContent, NcModal } from '@nextcloud/vue'
import { sortBy } from 'lodash'
import HeaderBar from '../components/Base/HeaderBar.vue'
import PollsAppIcon from '../components/AppIcons/PollsAppIcon.vue'

export default {
	name: 'Administration',

	components: {
		AppContent,
		NcActions,
		NcActionButton,
		NcEmptyContent,
		HeaderBar,
		NcModal,
		NcButton,
		PollsAppIcon,
		LoadingOverlay: () => import('../components/Base/LoadingOverlay.vue'),
		PollItem: () => import('../components/PollList/PollItem.vue'),
	},

	data() {
		return {
			isLoading: false,
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
		...mapGetters({
			filteredPolls: 'pollsAdmin/filtered',
		}),

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

		noPolls() {
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
			if (!this.filteredPolls(this.$route.params.type).find((poll) => poll.id === this.$store.state.poll.id)) {
				emit('polls:sidebar:toggle', { open: false })
			}

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
