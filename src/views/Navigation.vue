<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'

// Icons
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

import { Logger } from '../helpers/index.ts'
import CreateDlg from '../components/Create/CreateDlg.vue'
import { FilterType, usePollsStore } from '../stores/polls.ts'
import { usePollsAdminStore } from '../stores/pollsAdmin.ts'
import { useSessionStore } from '../stores/session.ts'

const iconSize = 20
const icons = [
	{ id: FilterType.Relevant, iconComponent: RelevantIcon },
	{ id: FilterType.My, iconComponent: MyPollsIcon },
	{ id: FilterType.Private, iconComponent: PrivatePollsIcon },
	{ id: FilterType.Participated, iconComponent: ParticipatedIcon },
	{ id: FilterType.Open, iconComponent: OpenPollIcon },
	{ id: FilterType.All, iconComponent: AllPollsIcon },
	{ id: FilterType.Closed, iconComponent: ClosedPollsIcon },
	{ id: FilterType.Archived, iconComponent: ArchivedPollsIcon },
]

const createDlgToggle = ref(false)
const showAdminSection = computed(() => getCurrentUser().isAdmin)

/**
 *
 * @param {string} iconId id of the icon
 */
function getIconComponent(iconId) {
	return icons.find((icon) => icon.id === iconId).iconComponent
}

/**
 *
 */
function toggleCreateDlg() {
	createDlgToggle.value = !createDlgToggle.value
	// if (createDlgToggle.value) {
	// 	this.$refs.createDlg.setFocus()
	// }
}

/**
 *
 */
function closeCreate() {
	createDlgToggle.value = false
}

/**
 *
 */
function loadPolls() {
	try {
		Logger.debug('Loading polls in navigation')
		pollsStore.load()

		if (getCurrentUser().isAdmin) {
			pollsAdminStore.load()
		}
	} catch {
		showError(t('polls', 'Error loading poll list'))
	}
}

/**
 * Archive or restore a poll
 * @param {number} pollId poll id to archive/unarchive
 */
function toggleArchive(pollId) {
	try {
		pollsStore.toggleArchive({ pollId })
	} catch {
		showError(t('polls', 'Error archiving/restoring poll.'))
	}
}

/**
 * Delete a poll
 * @param {number} pollId poll id to delete
 */
function deletePoll(pollId) {
	try {
		pollsStore.delete({ pollId })
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}

/**
 *
 * @param {number} pollId poll id to clone
 */
function clonePoll(pollId) {
	try {
		pollsStore.clone({ pollId })
	} catch {
		showError(t('polls', 'Error cloning poll.'))
	}
}

/**
 * Show the settings dialog
 */
function showSettings() {
	emit('polls:settings:show')
}

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()
const pollsAdminStore = usePollsAdminStore()

onMounted(() => {
	loadPolls()
})
</script>

<template>
	<NcAppNavigation>
		<NcAppNavigationNew
			v-if="sessionStore.appPermissions.pollCreation"
			button-class="icon-add"
			:text="t('polls', 'New poll')"
			@click="toggleCreateDlg" />
		<CreateDlg
			v-show="createDlgToggle"
			ref="createDlg"
			@close-create="closeCreate()" />

		<template #list>
			<NcAppNavigationItem
				v-for="pollCategory in pollsStore.categories"
				:key="pollCategory.id"
				:name="pollCategory.title"
				:allow-collapse="sessionStore.appSettings.navigationPollsInList"
				:pinned="pollCategory.pinned"
				:to="{ name: 'list', params: { type: pollCategory.id } }"
				:open="false">
				<template #icon>
					<Component
						:is="getIconComponent(pollCategory.id)"
						:size="iconSize" />
				</template>
				<template #counter>
					<NcCounterBubble
						:count="pollsStore.pollsCount[pollCategory.id]" />
				</template>
				<ul v-if="sessionStore.appSettings.navigationPollsInList">
					<PollNavigationItems
						v-for="poll in pollsStore.navigationList(pollCategory.id)"
						:key="poll.id"
						:poll="poll"
						@toggle-archive="toggleArchive(poll.id)"
						@clone-poll="clonePoll(poll.id)"
						@delete-poll="deletePoll(poll.id)" />
					<NcAppNavigationItem
						v-if="
							pollsStore.navigationList(pollCategory.id).length === 0
						"
						:name="t('polls', 'No polls found for this category')" />
					<NcAppNavigationItem
						v-if="
							pollsStore.pollsByCategory(pollCategory.id) >
							pollsStore.meta.maxPollsInNavigation
						"
						class="force-not-active"
						:to="{ name: 'list', params: { type: pollCategory.id } }"
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
				<NcAppNavigationItem
					v-if="sessionStore.appPermissions.comboView"
					:name="t('polls', 'Combine polls')"
					:to="{ name: 'combo' }">
					<template #icon>
						<ComboIcon :size="iconSize" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem
					v-if="showAdminSection"
					:name="t('polls', 'Administration')"
					:to="{ name: 'administration' }">
					<template #icon>
						<AdministrationIcon :size="iconSize" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem
					:name="t('polls', 'Preferences')"
					@click="showSettings()">
					<template #icon>
						<SettingsIcon :size="iconSize" />
					</template>
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>
</template>

<style lang="scss">
.closed {
	.app-navigation-entry-icon,
	.app-navigation-entry__title {
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
	flex: 0 0 auto;
}
</style>
