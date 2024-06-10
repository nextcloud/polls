<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebar :open.sync="showSidebar"
		:name="t('polls', 'Select polls to combine')"
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

<script>
import { NcAppSidebar, NcAppSidebarTab } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import { SideBarTabDatePolls } from '../components/SideBar/index.js'

export default {
	name: 'SideBarCombo',

	components: {
		SideBarTabDatePolls,
		NcAppSidebar,
		NcAppSidebarTab,
		PollsAppIcon,
	},

	data() {
		return {
			showSidebar: (window.innerWidth > 920),
		}
	},

	methods: {
		closeSideBar() {
			emit('polls:sidebar:toggle', { open: false })
		},
	},

}

</script>
