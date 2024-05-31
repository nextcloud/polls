<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
import { t } from '@nextcloud/l10n'

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
		t,
		closeSideBar() {
			emit('polls:sidebar:toggle', { open: false })
		},
	},
}

</script>
