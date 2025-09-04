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

import SidebarShareIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import SidebarConfigurationIcon from 'vue-material-design-icons/WrenchOutline.vue'

import SideBarTabPollGroup from '../components/SideBar/SideBarTabSharePollGroup.vue'
import SideBarTabConfigurationPollGroup from '../components/SideBar/SideBarTabConfigurationPollGroup.vue'

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
		<NcAppSidebarTab
			id="configuration"
			:order="1"
			:name="t('polls', 'Configuration')">
			<template #icon>
				<SidebarConfigurationIcon />
			</template>
			<SideBarTabConfigurationPollGroup />
		</NcAppSidebarTab>
		<NcAppSidebarTab id="sharing" :order="2" :name="t('polls', 'Sharing')">
			<template #icon>
				<SidebarShareIcon />
			</template>
			<SideBarTabPollGroup />
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>
