<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { emit } from '@nextcloud/event-bus'
	import { t } from '@nextcloud/l10n'

	import NcAppSidebar from '@nextcloud/vue/dist/Components/NcAppSidebar.js'
	import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab.js'

	import { PollsAppIcon } from '../components/AppIcons/index.js'
	import { SideBarTabDatePolls } from '../components/SideBar/index.js'

	function closeSideBar() {
		emit('polls:sidebar:toggle', { open: false })
	}

</script>

<template>
	<NcAppSidebar :name="t('polls', 'Select polls to combine')"
		@close="closeSideBar()">
		<NcAppSidebarTab :id="'polls'"
			:order="1"
			:name="t('polls', 'Polls')">
			<template #icon>
				<PollsAppIcon />
			</template>
			<SideBarTabDatePolls />
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>
