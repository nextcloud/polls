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
	<AppSidebar :active="initialTab" :title="t('polls', 'Details')" @close="$emit('closeSideBar')">
		<UserDiv slot="primary-actions" :user-id="event.owner" :description="t('polls', 'Owner')" />

		<AppSidebarTab :name="t('polls', 'Comments')" icon="icon-comment">
			<SideBarTabComments />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowEdit && event.type === 'datePoll'" :name="t('polls', 'Date options')" icon="icon-calendar">
			<SideBarTabDateOptions />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowEdit && event.type === 'textPoll'" :name="t('polls', 'Text options')" icon="icon-toggle-filelist">
			<SideBarTabTextOptions />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowEdit" :name="t('polls', 'Configuration')" icon="icon-settings">
			<SideBarTabConfiguration />
		</AppSidebarTab>

		<AppSidebarTab v-if="acl.allowEdit" :name="t('polls', 'Shares')" icon="icon-share">
			<SideBarTabShare />
		</AppSidebarTab>
	</AppSidebar>
</template>

<script>
import { AppSidebar, AppSidebarTab } from '@nextcloud/vue'

import SideBarTabConfiguration from './SideBarTabConfiguration'
import SideBarTabDateOptions from './SideBarTabDateOptions'
import SideBarTabTextOptions from './SideBarTabTextOptions'
import SideBarTabComments from './SideBarTabComments'
import SideBarTabShare from './SideBarTabShare'
import { mapState } from 'vuex'

export default {
	name: 'SideBar',
	components: {
		SideBarTabConfiguration,
		SideBarTabComments,
		SideBarTabDateOptions,
		SideBarTabTextOptions,
		SideBarTabShare,
		AppSidebar,
		AppSidebarTab
	},

	data() {
		return {
			initialTab: 'comments'
		}
	},

	computed: {
		...mapState({
			event: state => state.event,
			acl: state => state.acl
		})
	}

}

</script>

<style scoped lang="scss">

	ul {
		& > li {
			margin-bottom: 30px;
			& > .comment-item {
				display: flex;
				align-items: center;

				& > .date {
					right: 0;
					top: 5px;
					opacity: 0.5;
				}
			}
			& > .message {
				margin-left: 44px;
				flex: 1 1;
			}
		}
	}
</style>
