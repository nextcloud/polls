<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'

import { PollsAppIcon } from '../components/AppIcons/index.ts'
import { SideBarTabDatePolls } from '../components/SideBar/index.ts'
import { Event } from '../Types/index.ts'

/**
 *
 */
function closeSideBar() {
	emit(Event.SidebarToggle, { open: false })
}
</script>

<template>
	<NcAppSidebar
		:name="t('polls', 'Select polls to combine')"
		@close="closeSideBar()">
		<NcAppSidebarTab :id="'polls'" :order="1" :name="t('polls', 'Polls')">
			<template #icon>
				<PollsAppIcon />
			</template>
			<SideBarTabDatePolls />
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>
