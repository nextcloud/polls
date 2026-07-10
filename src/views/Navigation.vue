<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationSpacer from '@nextcloud/vue/components/NcAppNavigationSpacer'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import GoToIcon from 'vue-material-design-icons/ArrowRight.vue'
import GroupIcon from 'vue-material-design-icons/CodeBraces.vue'
import SettingsIcon from 'vue-material-design-icons/CogOutline.vue'
// Icons
import ComboIcon from 'vue-material-design-icons/VectorCombine.vue'
import ActionAddPoll from '../components/Actions/modules/ActionAddPoll.vue'
import PollCreateDlg from '../components/Create/PollCreateDlg.vue'
import PollNavigationItems from '../components/Navigation/PollNavigationItems.vue'
import { usePollGroupsStore } from '../stores/pollGroups'
import { usePollsStore } from '../stores/polls'
import { usePreferencesStore } from '../stores/preferences'
import { useSessionStore } from '../stores/session'
import { Event } from '../Types'

const router = useRouter()

const pollsStore = usePollsStore()
const pollGroupsStore = usePollGroupsStore()
const sessionStore = useSessionStore()
const preferencesStore = usePreferencesStore()

const iconSize = 20

const createDlgToggle = ref(false)

/**
 *
 * @param pollId
 */
function toggleArchive(pollId: number) {
	pollsStore.toggleArchive({ pollId }).catch(() => {
		showError(t('polls', 'Error archiving/restoring poll.'))
	})
}

/**
 * Delete a poll
 *
 * @param pollId poll id to delete
 */
function deletePoll(pollId: number) {
	pollsStore.delete({ pollId }).catch(() => {
		showError(t('polls', 'Error deleting poll.'))
	})
}

/**
 *
 * @param pollId poll id to clone
 */
function clonePoll(pollId: number) {
	pollsStore.clone({ pollId }).catch(() => {
		showError(t('polls', 'Error cloning poll.'))
	})
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
	pollsStore.load(false)
})
</script>

<template>
	<NcAppNavigation :aria-label="t('polls', 'Navigation')">
		<ActionAddPoll
			v-if="
				preferencesStore.useActionAddPollInNavigation
				&& sessionStore.appPermissions.pollCreation
			"
			buttonMode="navigation" />

		<NcAppNavigationNew
			v-else-if="
				preferencesStore.useNcAppNavigationNew
				&& sessionStore.appPermissions.pollCreation
			"
			buttonClass="icon-add"
			:text="t('polls', 'New poll')"
			@click="createDlgToggle = !createDlgToggle" />
		<PollCreateDlg
			v-show="createDlgToggle"
			@added="pollAdded"
			@close="createDlgToggle = false" />

		<template #list>
			<NcAppNavigationItem
				v-for="pollGroup in pollGroupsStore.pollGroupsSorted"
				:key="pollGroup.id"
				:name="pollGroup.name"
				:title="pollGroup.titleExt"
				allowCollapse
				:to="{
					name: 'group',
					params: { slug: pollGroup.slug },
				}"
				:open="false">
				<template #icon>
					<GroupIcon :size="iconSize" />
				</template>
				<template #counter>
					<NcCounterBubble
						:count="
							pollGroupsStore.countPollsInPollGroups[pollGroup.id]
						" />
				</template>
				<ul v-if="sessionStore.appSettings.navigationPollsInList">
					<PollNavigationItems
						v-for="poll in pollsStore.groupList(pollGroup.pollIds)"
						:key="poll.id"
						:poll="poll"
						@toggleArchive="toggleArchive(poll.id)"
						@clonePoll="clonePoll(poll.id)"
						@deletePoll="deletePoll(poll.id)" />
					<NcAppNavigationItem
						v-if="pollsStore.groupList(pollGroup.pollIds).length === 0"
						:name="t('polls', 'No polls found for this category')" />
					<NcAppNavigationItem
						v-if="
							pollsStore.groupList(pollGroup.pollIds).length
							> pollsStore.meta.maxPollsInNavigation
						"
						class="force-not-active"
						:to="{
							name: 'group',
							params: { slug: pollGroup.slug },
						}"
						:name="t('polls', 'Show all')">
						<template #icon>
							<GoToIcon :size="iconSize" />
						</template>
					</NcAppNavigationItem>
				</ul>
			</NcAppNavigationItem>
			<NcAppNavigationSpacer v-if="pollGroupsStore.pollGroups.length" />
			<NcAppNavigationItem
				v-for="pollCategory in pollsStore.navigationCategories"
				:key="pollCategory.id"
				v-bind="pollCategory"
				:allowCollapse="sessionStore.appSettings.navigationPollsInList"
				:to="{
					name: 'list',
					params: { type: pollCategory.id },
				}"
				:open="false">
				<template #icon>
					<Component :is="pollCategory.iconComponent" :size="iconSize" />
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
						@toggleArchive="toggleArchive(poll.id)"
						@clonePoll="clonePoll(poll.id)"
						@deletePoll="deletePoll(poll.id)" />
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
