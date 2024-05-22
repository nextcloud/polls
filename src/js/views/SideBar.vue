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
	<NcAppSidebar v-show="showSidebar"
		:active.sync="activeTab"
		:name="t('polls', 'Details')"
		@close="closeSideBar()">
		<NcAppSidebarTab v-if="permissions.edit"
			id="configuration"
			:order="1"
			:name="t('polls', 'Configuration')">
			<template #icon>
				<SidebarConfigurationIcon />
			</template>
			<SideBarTabConfiguration />
		</NcAppSidebarTab>

		<NcAppSidebarTab v-if="permissions.edit"
			id="options"
			:order="2"
			:name="t('polls', 'Options')">
			<template #icon>
				<SidebarOptionsIcon />
			</template>
			<SideBarTabOptions />
		</NcAppSidebarTab>

		<NcAppSidebarTab v-if="permissions.edit"
			id="sharing"
			:order="3"
			:name="t('polls', 'Sharing')">
			<template #icon>
				<SidebarShareIcon />
			</template>
			<SideBarTabShare />
		</NcAppSidebarTab>

		<NcAppSidebarTab v-if="permissions.comment"
			id="comments"
			:order="5"
			:name="t('polls', 'Comments')">
			<template #icon>
				<SidebarCommentsIcon />
			</template>
			<SideBarTabComments />
		</NcAppSidebarTab>

		<NcAppSidebarTab v-if="permissions.edit && useActivity"
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

<script>
import { NcAppSidebar, NcAppSidebarTab } from '@nextcloud/vue'
import { mapState } from 'vuex'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import SidebarConfigurationIcon from 'vue-material-design-icons/Wrench.vue'
import SidebarOptionsIcon from 'vue-material-design-icons/FormatListChecks.vue'
import SidebarShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import SidebarCommentsIcon from 'vue-material-design-icons/CommentProcessing.vue'
import SidebarActivityIcon from 'vue-material-design-icons/LightningBolt.vue'
import { SideBarTabConfiguration, SideBarTabComments, SideBarTabOptions, SideBarTabShare, SideBarTabActivity } from '../components/SideBar/index.js'

export default {
	name: 'SideBar',

	components: {
		SideBarTabConfiguration,
		SideBarTabComments,
		SideBarTabOptions,
		SideBarTabShare,
		SideBarTabActivity,
		NcAppSidebar,
		NcAppSidebarTab,
		SidebarActivityIcon,
		SidebarConfigurationIcon,
		SidebarOptionsIcon,
		SidebarShareIcon,
		SidebarCommentsIcon,
	},

	data() {
		return {
			activeTab: t('polls', 'Comments').toLowerCase(),
			showSidebar: (window.innerWidth > 920),
		}
	},

	computed: {
		...mapState({
			permissions: (state) => state.poll.permissions,
			useActivity: (state) => state.acl.appSettings.useActivity,
		}),

	},

	created() {
		subscribe('polls:sidebar:changeTab', (payload) => {
			this.activeTab = payload?.activeTab ?? this.activeTab
		})

		subscribe('polls:sidebar:toggle', (payload) => {
			emit('polls:sidebar:changeTab', { activeTab: payload?.activeTab })
			this.showSidebar = payload?.open ?? !this.showSidebar
		})
	},

	beforeDestroy() {
		unsubscribe('polls:sidebar:changeTab')
		unsubscribe('polls:sidebar:toggle')
	},

	methods: {
		closeSideBar() {
			emit('polls:sidebar:toggle', { open: false })
		},
	},
}

</script>
