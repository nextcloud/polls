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

<template lang="html">
	<AppNavigation>
		<AppNavigationNew button-class="icon-add" :text="t('polls', 'Add new Poll')" @click="toggleCreateDlg" />
		<CreateDlg v-show="createDlg" ref="createDlg" @close-create="closeCreate()" />
		<template #list>
			<AppNavigationItem v-for="(pollCategory) in pollCategories"
				:key="pollCategory.id"
				:title="pollCategory.title"
				:allow-collapse="true"
				:pinned="pollCategory.pinned"
				:icon="pollCategory.icon"
				:to="{ name: 'list', params: {type: pollCategory.id}}"
				:open="false">
				<ul>
					<PollNavigationItems v-for="(poll) in filteredPolls(pollCategory.id)"
						:key="poll.id"
						:poll="poll"
						@toggle-archive="toggleArchive(poll.id)"
						@clone-poll="clonePoll(poll.id)"
						@delete-poll="deletePoll(poll.id)" />
				</ul>
			</AppNavigationItem>
		</template>
		<template #footer>
			<AppNavigationItem v-if="showAdminSection"
				:title="t('core', 'Administration')"
				icon="icon-settings"
				:to="{ name: 'administration' }" />
			<AppNavigationItem :title="t('core', 'Settings')" icon="icon-settings" @click="showSettings()" />
		</template>
	</AppNavigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppNavigationItem } from '@nextcloud/vue'
import { mapGetters, mapState } from 'vuex'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import CreateDlg from '../components/Create/CreateDlg'
import PollNavigationItems from '../components/Navigation/PollNavigationItems'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationItem,
		CreateDlg,
		PollNavigationItems,
	},

	data() {
		return {
			showSettingsDlg: false,
			createDlg: false,
		}
	},

	computed: {
		...mapState({
			pollCategories: (state) => state.polls.categories,
		}),

		...mapGetters({
			filteredPolls: 'polls/filtered',
		}),

		showAdminSection() {
			return getCurrentUser().isAdmin
		},
	},

	created() {
		this.updatePolls()
		subscribe('update-polls', () => {
			this.updatePolls()
		})
	},

	beforeDestroy() {
		window.clearInterval(this.reloadTimer)
		unsubscribe('update-polls')
	},

	methods: {
		closeCreate() {
			this.createDlg = false
		},

		toggleCreateDlg() {
			this.createDlg = !this.createDlg
			if (this.createDlg) {
				this.$refs.createDlg.setFocus()
			}
		},

		showSettings() {
			emit('show-settings')
		},

		async updatePolls() {
			const dispatches = []

			dispatches.push('polls/list')

			if (getCurrentUser().isAdmin) {
				dispatches.push('pollsAdmin/load')
			}

			try {
				const requests = dispatches.map((dispatches) => this.$store.dispatch(dispatches))
				await Promise.all(requests)
			} catch {
				showError(t('polls', 'Error loading poll list'))
			}
		},

		async clonePoll(pollId) {
			try {
				const response = await this.$store.dispatch('poll/clone', { pollId })
				emit('update-polls')
				this.$router.push({ name: 'vote', params: { id: response.data.id } })
			} catch {
				showError(t('polls', 'Error cloning poll.'))
			}
		},

		async toggleArchive(pollId) {
			try {
				await this.$store.dispatch('poll/toggleArchive', { pollId })
				emit('update-polls')
			} catch {
				showError(t('polls', 'Error archiving/restoring poll.'))
			}
		},

		async deletePoll(pollId) {
			try {
				await this.$store.dispatch('poll/delete', { pollId })
				// if we delete current selected poll,
				// reload deleted polls route
				if (this.$route.params.id && this.$route.params.id === pollId) {
					this.$router.push({ name: 'list', params: { type: 'deleted' } })
				}
			} catch {
				showError(t('polls', 'Error deleting poll.'))
			}
		},
	},
}
</script>

<style lang="scss">
	.closed {
		.app-navigation-entry-icon, .app-navigation-entry__title {
			opacity: 0.6;
		}
	}

</style>
