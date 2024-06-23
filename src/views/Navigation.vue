<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigation>
		<NcAppNavigationNew v-if="pollsStore.meta.permissions.pollCreationAllowed"
			button-class="icon-add"
			:text="t('polls', 'New poll')"
			@click="toggleCreateDlg" />
		<CreateDlg v-show="createDlg" ref="createDlg" @close-create="closeCreate()" />

		<template #list>
			<NcAppNavigationItem v-for="(pollCategory) in pollsStore.categories"
				:key="pollCategory.id"
				:name="pollCategory.title"
				:allow-collapse="sessionStore.appSettings.navigationPollsInList"
				:pinned="pollCategory.pinned"
				:to="{ name: 'list', params: {type: pollCategory.id}}"
				:open="false">
				<template #icon>
					<Component :is="getIconComponent(pollCategory.id)" :size="iconSize" />
				</template>
				<template #counter>
					<NcCounterBubble>
						{{ pollsStore.pollsCount[pollCategory.id] }}
					</NcCounterBubble>
				</template>
				<ul v-if="sessionStore.appSettings.navigationPollsInList">
					<PollNavigationItems v-for="(poll) in pollsStore.navigationList(pollCategory.id)"
						:key="poll.id"
						:poll="poll"
						@toggle-archive="toggleArchive(poll.id)"
						@clone-poll="clonePoll(poll.id)"
						@delete-poll="deletePoll(poll.id)" />
					<NcAppNavigationItem v-if="pollsStore.navigationList(pollCategory.id).length === 0"
						:name="t('polls', 'No polls found for this category')" />
					<NcAppNavigationItem v-if="pollsStore.pollsByCategory(pollCategory.id) > pollsStore.meta.maxPollsInNavigation"
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
				<NcAppNavigationItem v-if="pollsStore.meta.permissions.comboAllowed"
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

import { mapStores } from 'pinia'
import { NcAppNavigation, NcAppNavigationNew, NcAppNavigationItem, NcCounterBubble } from '@nextcloud/vue'
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
import { t } from '@nextcloud/l10n'
import { usePollsStore } from '../stores/polls.ts'
import { useSessionStore } from '../stores/session.ts'
import { usePollsAdminStore } from '../stores/pollsAdmin.ts'

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
		...mapStores(useSessionStore, usePollsStore, usePollsAdminStore ),

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
		t,
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
				this.pollsStore.load()

				if (getCurrentUser().isAdmin) {
					this.pollsAdminStore.load()
				}
			} catch {
				showError(t('polls', 'Error loading poll list'))
			}
		},

		async clonePoll(pollId) {
			try {
				const response = await this.pollsStore.clone({ pollId })
				this.$router.push({ name: 'vote', params: { id: response.data.id } })
			} catch {
				showError(t('polls', 'Error cloning poll.'))
			}
		},

		async toggleArchive(pollId) {
			try {
				await this.pollsStore.toggleArchive({ pollId })
			} catch {
				showError(t('polls', 'Error archiving/restoring poll.'))
			}
		},

		async deletePoll(pollId) {
			try {
				await this.pollsStore.delete({ pollId })
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
