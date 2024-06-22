<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="poll-header-buttons">
		<UserMenu v-if="showUserMenu" />
		<NcPopover :focus-trap="false">
			<template #trigger>
				<NcButton :title="caption"
					:aria-label="caption"
					type="tertiary">
					<template #icon>
						<PollInformationIcon />
					</template>
				</NcButton>
			</template>
			<PollInformation />
		</NcPopover>
		<ExportPoll v-if="pollStore.permissions.pollDownload" />
		<ActionToggleSidebar v-if="pollStore.permissions.edit || pollStore.permissions.comment" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcButton, NcPopover } from '@nextcloud/vue'
import { ActionToggleSidebar } from '../Actions/index.js'
import PollInformationIcon from 'vue-material-design-icons/InformationOutline.vue'
import PollInformation from '../Poll/PollInformation.vue'
import UserMenu from '../User/UserMenu.vue'
import ExportPoll from '../Export/ExportPoll.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'PollHeaderButtons',
	components: {
		ActionToggleSidebar,
		PollInformationIcon,
		NcPopover,
		NcButton,
		UserMenu,
		ExportPoll,
		PollInformation,
	},

	data() {
		return {
			caption: t('polls', 'Poll informations'),
		}
	},

	computed: {
		...mapStores(usePollStore),

		showUserMenu() {
			return this.$route.name !== 'publicVote' || this.pollStore.permissions.vote || this.pollStore.permissions.subscribe
		},
	},

	beforeDestroy() {
		this.pollStore.$reset()
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
