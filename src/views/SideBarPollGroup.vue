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
import { Event } from '../Types/index.ts'

import SidebarShareIcon from 'vue-material-design-icons/ShareVariant.vue'

import { SideBarTabPollGroupShare } from '../components/SideBar/index.js'

const showSidebar = ref(window.innerWidth > 920)
const activeTab = ref(t('polls', 'Shares').toLowerCase())

onMounted(() => {
	subscribe(Event.SidebarToggle, (payload) => {
		showSidebar.value = payload?.open ?? !showSidebar.value
	})
})

onUnmounted(() => {
	unsubscribe(Event.SidebarToggle, () => {
		activeTab.value = 'sharing'
	})
})

/**
 *
 */
function closeSideBar() {
	emit(Event.SidebarToggle, { open: false })
}
</script>

<template>
	<NcAppSidebar
		v-show="showSidebar"
		v-model="activeTab"
		:name="t('polls', 'Details')"
		@close="closeSideBar()">
		<NcAppSidebarTab id="sharing" :order="3" :name="t('polls', 'Sharing')">
			<template #icon>
				<SidebarShareIcon />
			</template>
			<SideBarTabPollGroupShare />
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>
