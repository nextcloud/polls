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
		<AppNavigationNew v-if="isPollCreationAllowed"
			button-class="icon-add"
			:text="t('polls', 'Add new Poll')"
			@click="toggleCreateDlg" />
		<CreateDlg v-show="createDlg" ref="createDlg" @close-create="closeCreate()" />

		<template #list>
			<AppNavigationItem v-for="(pollCategory) in pollCategories"
				:key="pollCategory.id"
				:title="pollCategory.title"
				:allow-collapse="true"
				:pinned="pollCategory.pinned"
				:to="{ name: 'list', params: {type: pollCategory.id}}"
				:open="false">
				<template #icon>
					<Component :is="getIconComponent(pollCategory.id)" :size="iconSize" />
				</template>
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
			<AppNavigationItem v-if="isComboActivated"
				:title="t('polls', 'Combine polls')"
				:to="{ name: 'combo' }">
				<template #icon>
					<ComboIcon :size="iconSize" />
				</template>
			</AppNavigationItem>
			<AppNavigationItem v-if="showAdminSection"
				:title="t('polls', 'Administration')"
				:to="{ name: 'administration' }">
				<template #icon>
					<AdministrationIcon :size="iconSize" />
				</template>
			</AppNavigationItem>
			<AppNavigationItem :title="t('polls', 'Polls settings')"
				@click="showSettings()">
				<template #icon>
					<SettingsIcon :size="iconSize" />
				</template>
			</AppNavigationItem>
		</template>
	</AppNavigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppNavigationItem } from '@nextcloud/vue'
import { mapGetters, mapState } from 'vuex'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import CreateDlg from '../components/Create/CreateDlg.vue'
import PollNavigationItems from '../components/Navigation/PollNavigationItems.vue'
import ComboIcon from 'vue-material-design-icons/VectorCombine.vue'
import AdministrationIcon from 'vue-material-design-icons/Cog.vue'
import SettingsIcon from 'vue-material-design-icons/AccountCog.vue'
import RelevantIcon from 'vue-material-design-icons/ExclamationThick.vue'
import MyPollsIcon from 'vue-material-design-icons/Crown.vue'
import PrivatePollsIcon from 'vue-material-design-icons/Key.vue'
import ParticipatedIcon from 'vue-material-design-icons/AccountCheck.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import AllPollsIcon from 'vue-material-design-icons/Poll.vue'
import ClosedPollsIcon from 'vue-material-design-icons/Lock.vue'
import ArchivedPollsIcon from 'vue-material-design-icons/Archive.vue'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationItem,
		CreateDlg,
		PollNavigationItems,
		ComboIcon,
		SettingsIcon,
		AdministrationIcon,
	},

	data() {
		return {
			iconSize: 20,
			createDlg: false,
			icons: [
				{ id: 'relevant', iconComponent: RelevantIcon },
				{ id: 'my', iconComponent: MyPollsIcon },
				{ id: 'private', iconComponent: PrivatePollsIcon },
				{ id: 'participated', iconComponent: ParticipatedIcon },
				{ id: 'open', iconComponent: OpenPollIcon },
				{ id: 'all', iconComponent: AllPollsIcon },
				{ id: 'closed', iconComponent: ClosedPollsIcon },
				{ id: 'archived', iconComponent: ArchivedPollsIcon },
			],
		}
	},

	computed: {
		...mapState({
			isPollCreationAllowed: (state) => state.polls.isPollCreationAllowed,
			isComboActivated: (state) => state.polls.isComboAllowed,
		}),

		...mapGetters({
			pollCategories: 'polls/categories',
			filteredPolls: 'polls/filtered',
		}),

		showAdminSection() {
			return getCurrentUser().isAdmin
		},
	},

	created() {
		this.loadPolls()
	},

	beforeDestroy() {
		window.clearInterval(this.reloadTimer)
	},

	methods: {
		closeCreate() {
			this.createDlg = false
		},

		getIconComponent(iconId) {
			return this.icons.find((icon) => icon.id === iconId).iconComponent
		},

		toggleCreateDlg() {
			this.createDlg = !this.createDlg
			if (this.createDlg) {
				this.$refs.createDlg.setFocus()
			}
		},

		showSettings() {
			emit('polls:settings:show')
		},

		async loadPolls() {
			try {
				this.$store.dispatch('polls/list')

				if (getCurrentUser().isAdmin) {
					this.$store.dispatch('pollsAdmin/list')
				}
			} catch {
				showError(t('polls', 'Error loading poll list'))
			}
		},

		async clonePoll(pollId) {
			try {
				const response = await this.$store.dispatch('poll/clone', { pollId })
				this.$router.push({ name: 'vote', params: { id: response.data.id } })
			} catch {
				showError(t('polls', 'Error cloning poll.'))
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
