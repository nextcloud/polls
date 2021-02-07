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
	<AppSidebar ref="sideBar"
		:active="active"
		:title="t('polls', 'Details')"
		@close="closeSideBar()">
		<AppSidebarTab v-if="acl.allowEdit"
			:id="'configuration'"
			:order="1"
			:name="t('polls', 'Configuration')"
			icon="icon-settings">
			<SideBarTabConfiguration />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowEdit"
			:id="'options'"
			:order="2"
			:name="t('polls', 'Options')"
			icon="icon-toggle-filelist">
			<SideBarTabOptions />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowEdit"
			:id="'shares'"
			:order="3"
			:name="t('polls', 'Shares')"
			icon="icon-share">
			<SideBarTabShare />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowComment" :id="'comments'"
			:order="4"
			:name="t('polls', 'Comments')"
			icon="icon-comment">
			<SideBarTabComments />
		</AppSidebarTab>
	</AppSidebar>
</template>

<script>
import { AppSidebar, AppSidebarTab } from '@nextcloud/vue'

import SideBarTabConfiguration from './SideBarTabConfiguration'
import SideBarTabOptions from './SideBarTabOptions'
import SideBarTabComments from './SideBarTabComments'
import SideBarTabShare from './SideBarTabShare'
import { mapState } from 'vuex'
import { emit } from '@nextcloud/event-bus'

export default {
	name: 'SideBar',

	components: {
		SideBarTabConfiguration,
		SideBarTabComments,
		SideBarTabOptions,
		SideBarTabShare,
		AppSidebar,
		AppSidebarTab,
	},

	props: {
		active: {
			type: String,
			default: t('polls', 'Comments').toLowerCase(),
		},
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
		}),
	},
	methods: {
		closeSideBar() {
			emit('toggle-sidebar', { open: false })
		},
	},

}

</script>
