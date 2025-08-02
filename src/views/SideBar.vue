<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import { Event } from '../Types'

import { usePollStore } from '../stores/poll'
import { useSessionStore } from '../stores/session'
import SideBarTabConfiguration from '../components/SideBar/SideBarTabConfiguration.vue'
import SideBarTabComments from '../components/SideBar/SideBarTabComments.vue'
import SideBarTabOptions from '../components/SideBar/SideBarTabOptions.vue'
import SideBarTabShare from '../components/SideBar/SideBarTabShare.vue'
import SideBarTabActivity from '../components/SideBar/SideBarTabActivity.vue'
import SidebarConfigurationIcon from 'vue-material-design-icons/Wrench.vue'
import SidebarOptionsIcon from 'vue-material-design-icons/FormatListChecks.vue'
import SidebarShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import SidebarCommentsIcon from 'vue-material-design-icons/CommentProcessing.vue'
import SidebarActivityIcon from 'vue-material-design-icons/LightningBolt.vue'

const pollStore = usePollStore()
const sessionStore = useSessionStore()

const showSidebar = ref(window.innerWidth > 920)
const activeTab = ref(t('polls', 'Comments').toLowerCase())

onMounted(() => {
	subscribe(Event.SidebarToggle, (payload) => {
		showSidebar.value = payload?.open ?? !showSidebar.value
		activeTab.value = payload?.activeTab ?? activeTab.value
	})
	subscribe(Event.SidebarChangeTab, (payload) => {
		activeTab.value = payload?.activeTab ?? activeTab.value
	})
})

onUnmounted(() => {
	unsubscribe(Event.SidebarToggle, () => {
		activeTab.value = 'comments'
	})
	unsubscribe(Event.SidebarChangeTab, () => {
		showSidebar.value = false
	})
})

function closeSideBar() {
	emit(Event.SidebarToggle, { open: false })
}
</script>

<template>
	<NcAppSidebar
		v-show="showSidebar"
		v-model:active="activeTab"
		:name="t('polls', 'Details')"
		@close="closeSideBar()">
		<NcAppSidebarTab
			v-if="pollStore.permissions.edit"
			id="configuration"
			:order="1"
			:name="t('polls', 'Configuration')">
			<template #icon>
				<SidebarConfigurationIcon />
			</template>
			<SideBarTabConfiguration />
		</NcAppSidebarTab>

		<NcAppSidebarTab
			v-if="pollStore.permissions.edit"
			id="options"
			:order="2"
			:name="t('polls', 'Options')">
			<template #icon>
				<SidebarOptionsIcon />
			</template>
			<SideBarTabOptions />
		</NcAppSidebarTab>

		<NcAppSidebarTab
			v-if="pollStore.permissions.edit"
			id="sharing"
			:order="3"
			:name="t('polls', 'Sharing')">
			<template #icon>
				<SidebarShareIcon />
			</template>
			<SideBarTabShare />
		</NcAppSidebarTab>

		<NcAppSidebarTab
			v-if="pollStore.permissions.comment"
			id="comments"
			:order="5"
			:name="t('polls', 'Comments')">
			<template #icon>
				<SidebarCommentsIcon />
			</template>
			<SideBarTabComments />
		</NcAppSidebarTab>

		<NcAppSidebarTab
			v-if="pollStore.permissions.edit && sessionStore.appSettings.useActivity"
			id="activity"
			:order="6"
			:name="t('polls', 'Activity')">
			<template #icon>
				<SidebarActivityIcon />
			</template>
			<SideBarTabActivity />
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>
