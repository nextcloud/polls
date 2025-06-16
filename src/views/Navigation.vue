<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, onMounted } from 'vue'
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
import AdministrationIcon from 'vue-material-design-icons/ShieldCrown.vue'
import SettingsIcon from 'vue-material-design-icons/Cog.vue'
import RelevantIcon from 'vue-material-design-icons/ExclamationThick.vue'
import MyPollsIcon from 'vue-material-design-icons/Crown.vue'
import PrivatePollsIcon from 'vue-material-design-icons/Key.vue'
import ParticipatedIcon from 'vue-material-design-icons/AccountCheck.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import AllPollsIcon from 'vue-material-design-icons/Poll.vue'
import ClosedPollsIcon from 'vue-material-design-icons/Lock.vue'
import ArchivedPollsIcon from 'vue-material-design-icons/Archive.vue'
import GoToIcon from 'vue-material-design-icons/ArrowRight.vue'
import GroupIcon from 'vue-material-design-icons/CodeBraces.vue'

import { Logger } from '../helpers/index.ts'
import PollCreateDlg from '../components/Create/PollCreateDlg.vue'
import { FilterType, usePollsStore } from '../stores/polls.ts'
import { useSessionStore } from '../stores/session.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import ActionAddPoll from '../components/Actions/modules/ActionAddPoll.vue'
import { ButtonMode, Event } from '../Types/index.ts'
import { useRouter } from 'vue-router'
import { NcAppNavigationSpacer } from '@nextcloud/vue'

const router = useRouter()

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()
const preferencesStore = usePreferencesStore()

const iconSize = 20
const icons = {
	[FilterType.Relevant]: {
		id: FilterType.Relevant,
		iconComponent: RelevantIcon,
	},
	[FilterType.My]: {
		id: FilterType.My,
		iconComponent: MyPollsIcon,
	},
	[FilterType.Private]: {
		id: FilterType.Private,
		iconComponent: PrivatePollsIcon,
	},
	[FilterType.Participated]: {
		id: FilterType.Participated,
		iconComponent: ParticipatedIcon,
	},
	[FilterType.Open]: {
		id: FilterType.Open,
		iconComponent: OpenPollIcon,
	},
	[FilterType.All]: {
		id: FilterType.All,
		iconComponent: AllPollsIcon,
	},
	[FilterType.Closed]: {
		id: FilterType.Closed,
		iconComponent: ClosedPollsIcon,
	},
	[FilterType.Archived]: {
		id: FilterType.Archived,
		iconComponent: ArchivedPollsIcon,
	},
	[FilterType.Admin]: {
		id: FilterType.Admin,
		iconComponent: AdministrationIcon,
	},
}

const createDlgToggle = ref(false)

/**
 *
 * @param iconId
 */
function getIconComponent(iconId: FilterType) {
	return icons[iconId].iconComponent
}

/**
 *
 */
function loadPolls() {
	try {
		Logger.debug('Loading polls in navigation')
		pollsStore.load()
	} catch {
		showError(t('polls', 'Error loading poll list'))
	}
}

/**
 *
 * @param pollId
 */
function toggleArchive(pollId: number) {
	try {
		pollsStore.toggleArchive({ pollId })
	} catch {
		showError(t('polls', 'Error archiving/restoring poll.'))
	}
}

/**
 * Delete a poll
 *
 * @param pollId poll id to delete
 */
function deletePoll(pollId: number) {
	try {
		pollsStore.delete({ pollId })
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}

/**
 *
 * @param pollId poll id to clone
 */
function clonePoll(pollId: number) {
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
	emit(Event.ShowSettings, null)
}

/**
 *
 * @param payLoad
 * @param payLoad.id
 * @param payLoad.title
 */
async function pollAdded(payLoad: { id: number; title: string }) {
	createDlgToggle.value = false
	router.push({
		name: 'vote',
		params: { id: payLoad.id },
	})
}

onMounted(() => {
	loadPolls()
})
</script>

<template>
	<NcAppNavigation>
		<ActionAddPoll
			v-if="preferencesStore.useActionAddPollInNavigation"
			:button-mode="ButtonMode.Navigation" />

		<NcAppNavigationNew
			v-if="preferencesStore.useNcAppNavigationNew"
			button-class="icon-add"
			:text="t('polls', 'New poll')"
			@click="createDlgToggle = !createDlgToggle" />
		<PollCreateDlg
			v-show="createDlgToggle"
			@added="pollAdded"
			@close="createDlgToggle = false" />

		<template #list>
			<NcAppNavigationItem
				v-for="pollGroup in pollsStore.groups"
				:key="pollGroup.id"
				:name="pollGroup.title"
				:title="pollGroup.titleExt"
				:allow-collapse="sessionStore.appSettings.navigationPollsInList"
				:open="false">
				<template #icon>
					<GroupIcon :size="iconSize" />
				</template>
				<template #counter>
					<NcCounterBubble :count="pollGroup.polls.length" />
				</template>
				<ul v-if="sessionStore.appSettings.navigationPollsInList">
					<PollNavigationItems
						v-for="poll in pollsStore.groupList(pollGroup.polls)"
						:key="poll.id"
						:poll="poll"
						@toggle-archive="toggleArchive(poll.id)"
						@clone-poll="clonePoll(poll.id)"
						@delete-poll="deletePoll(poll.id)" />
					<NcAppNavigationItem
						v-if="pollsStore.groupList(pollGroup.polls).length === 0"
						:name="t('polls', 'No polls found for this category')" />
					<NcAppNavigationItem
						v-if="
							pollsStore.groupList(pollGroup.polls).length
							> pollsStore.meta.maxPollsInNavigation
						"
						class="force-not-active"
						:to="{
							name: 'list',
							params: { type: pollGroup.id },
						}"
						:name="t('polls', 'Show all')">
						<template #icon>
							<GoToIcon :size="iconSize" />
						</template>
					</NcAppNavigationItem>
				</ul>
			</NcAppNavigationItem>
			<NcAppNavigationSpacer v-if="pollsStore.groups.length"/>
			<NcAppNavigationItem
				v-for="pollCategory in pollsStore.navigationCategories"
				:key="pollCategory.id"
				:name="pollCategory.title"
				:title="pollCategory.titleExt"
				:allow-collapse="sessionStore.appSettings.navigationPollsInList"
				:pinned="pollCategory.pinned"
				:to="{
					name: 'list',
					params: { type: pollCategory.id },
				}"
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
							pollsStore.navigationList(pollCategory.id).length
							> pollsStore.meta.maxPollsInNavigation
						"
						class="force-not-active"
						:to="{
							name: 'list',
							params: { type: pollCategory.id },
						}"
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
					:name="t('polls', 'Polls settings')"
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
// TODO: hack for the navigation list
.app-polls {
	.app-navigation__body {
		overflow: revert;
	}

	.app-navigation-footer {
		margin-inline-start: 10px;
	}
}

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
</style>
