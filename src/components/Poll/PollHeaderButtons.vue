<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'

import PollInformationIcon from 'vue-material-design-icons/InformationOutline.vue'

import ActionToggleSidebar from '../Actions/modules/ActionToggleSidebar.vue'
import PollInformation from '../Poll/PollInformation.vue'
import UserMenu from '../User/UserMenu.vue'
import ExportPoll from '../Export/ExportPoll.vue'
import { usePollStore } from '../../stores/poll.ts'
import { useSessionStore } from '../../stores/session.ts'

const route = useRoute()
const pollStore = usePollStore()
const sessionStore = useSessionStore()
const caption = t('polls', 'Poll information')

const showUserMenu = computed(
	() =>
		route.name !== 'publicVote'
		|| pollStore.permissions.vote
		|| pollStore.permissions.subscribe,
)

onBeforeUnmount(() => {
	pollStore.$reset()
})
</script>

<template>
	<div class="poll-header-buttons">
		<UserMenu v-if="showUserMenu" />
		<NcPopover close-on-click-outside no-focus-trap>
			<template #trigger>
				<NcButton
					:title="caption"
					:aria-label="caption"
					:variant="'tertiary'">
					<template #icon>
						<PollInformationIcon />
					</template>
				</NcButton>
			</template>
			<PollInformation />
		</NcPopover>
		<ExportPoll v-if="sessionStore.appPermissions.pollDownload" />
		<ActionToggleSidebar
			v-if="pollStore.permissions.edit || pollStore.permissions.comment" />
	</div>
</template>

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
