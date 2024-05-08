<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigation>
		<NcAppNavigationNew v-if="isPollCreationAllowed"
			button-class="icon-add"
			:text="t('polls', 'New poll')"
			@click="toggleCreateDlg" />
		<CreateDlg v-show="createDlg" ref="createDlg" @close-create="closeCreate()" />

		<template #list>
			<NcAppNavigationItem v-for="(pollCategory) in pollCategories"
				:key="pollCategory.id"
				:name="pollCategory.title"
				:allow-collapse="navigationPollsInList"
				:pinned="pollCategory.pinned"
				:to="{ name: 'list', params: {type: pollCategory.id}}"
				:open="false">
				<template #icon>
					<Component :is="getIconComponent(pollCategory.id)" :size="iconSize" />
				</template>
				<template #counter>
					<NcCounterBubble>
						{{ countPolls(pollCategory.id) }}
					</NcCounterBubble>
				</template>
				<ul v-if="navigationPollsInList">
					<PollNavigationItems v-for="(poll) in filteredPolls(pollCategory.id)"
						:key="poll.id"
						:poll="poll"
						@toggle-archive="toggleArchive(poll.id)"
						@clone-poll="clonePoll(poll.id)"
						@delete-poll="deletePoll(poll.id)" />
					<NcAppNavigationItem v-if="filteredPolls(pollCategory.id).length === 0"
						:name="t('polls', 'No polls found for this category')" />
					<NcAppNavigationItem v-if="countPolls(pollCategory.id) > maxPolls"
						class="force-not-active"
						:to="{ name: 'list', params: {type: pollCategory.id}}"
						:name="t('polls', 'Show all')">
						<template #icon>
							<GoToIcon :size="iconSize" />
						</template>
					</NcAppNavigationItem>
				</ul>
			</NcAppNavigationItem>
		</template>

		<template #footer>
			<ul class="app-navigation-footer">
				<NcAppNavigationItem v-if="isComboActivated"
					:name="t('polls', 'Combine polls')"
					:to="{ name: 'combo' }">
					<template #icon>
						<ComboIcon :size="iconSize" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="showAdminSection"
					:name="t('polls', 'Administration')"
					:to="{ name: 'administration' }">
					<template #icon>
						<AdministrationIcon :size="iconSize" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem :name="t('polls', 'Preferences')" @click="showSettings()">
					<template #icon>
						<SettingsIcon :size="iconSize" />
					</template>
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>
</template>

<script>

import { NcAppNavigation, NcAppNavigationNew, NcAppNavigationItem, NcCounterBubble } from '@nextcloud/vue'
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
import GoToIcon from 'vue-material-design-icons/ArrowRight.vue'

export default {
	name: 'Navigation',
	components: {
		NcAppNavigation,
		NcAppNavigationNew,
		NcAppNavigationItem,
		NcCounterBubble,
		CreateDlg,
		GoToIcon,
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
			isPollCreationAllowed: (state) => state.polls.meta.permissions.pollCreationAllowed,
			isComboActivated: (state) => state.polls.meta.permissions.comboAllowed,
			navigationPollsInList: (state) => state.acl.appSettings.navigationPollsInList,
			maxPolls: (state) => state.polls.meta.maxPollsInNavigation,
		}),

		...mapGetters({
			pollCategories: 'polls/categories',
			filteredPolls: 'polls/filteredByCategory',
			countPolls: 'polls/countByCategory',
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

	.app-navigation-entry-wrapper.force-not-active .app-navigation-entry.active {
		background-color: transparent !important;
		* {
			color: unset !important;
		}
	}

	.app-navigation-footer {
		// height: auto !important;
		// overflow: hidden !important;
		// padding-top: 0 !important;
		flex: 0 0 auto;
	}
</style>
