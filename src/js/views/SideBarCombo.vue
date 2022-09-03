<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcAppSidebar ref="sideBar"
		:active="active"
		:title="t('polls', 'Select polls to combine')"
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
import { mapGetters } from 'vuex'
import { emit } from '@nextcloud/event-bus'
import PollsAppIcon from '../components/AppIcons/PollsAppIcon.vue'

export default {
	name: 'SideBarCombo',

	components: {
		SideBarTabDatePolls: () => import('../components/SideBar/SideBarTabDatePolls.vue'),
		NcAppSidebar,
		NcAppSidebarTab,
		PollsAppIcon,
	},

	props: {
		active: {
			type: String,
			default: t('polls', 'Polls').toLowerCase(),
		},
	},

	computed: {
		...mapGetters({
			polls: 'polls/datePolls',
		}),
	},
	methods: {
		closeSideBar() {
			emit('polls:sidebar:toggle', { open: false })
		},
	},

}

</script>
