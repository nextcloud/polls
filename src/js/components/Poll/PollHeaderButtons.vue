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
	<div class="poll-header-buttons">
		<UserMenu />
		<Popover>
			<template #trigger>
				<VueButton v-tooltip="t('polls', 'Poll informations')"
					type="tertiary">
					<PollInformationIcon />
				</VueButton>
			</template>
			<PollInformation />
		</Popover>
		<ExportPoll v-if="allowPollDownload" />
		<ActionToggleSidebar v-if="allowEdit || allowComment" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { Button as VueButton, Popover } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import ActionToggleSidebar from '../Actions/ActionToggleSidebar'
import UserMenu from '../User/UserMenu'
import PollInformationIcon from 'vue-material-design-icons/InformationOutline.vue'

export default {
	name: 'PollHeaderButtons',
	components: {
		ActionToggleSidebar,
		PollInformationIcon,
		Popover,
		VueButton,
		UserMenu,
		ExportPoll: () => import('../Export/ExportPoll'),
		PollInformation: () => import('../Poll/PollInformation'),
	},

	computed: {
		...mapState({
			allowComment: (state) => state.poll.allowComment,
			allowEdit: (state) => state.poll.acl.allowEdit,
			allowPollDownload: (state) => state.poll.acl.allowPollDownload,
		}),
	},

	created() {
		emit('polls:sidebar:toggle', { open: (window.innerWidth > 920) })
	},

	beforeDestroy() {
		this.$store.dispatch({ type: 'poll/reset' })
	},
}

</script>

<style lang="scss">
.poll-header-buttons {
	display: flex;
	flex: 0;
	justify-content: flex-end;
	align-self: flex-end;
	border-radius: var(--border-radius-pill);
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

</style>
