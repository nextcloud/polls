<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, onBeforeUnmount } from 'vue'
	import { useRoute } from 'vue-router'
	import { t } from '@nextcloud/l10n'
	
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	import NcPopover from '@nextcloud/vue/dist/Components/NcPopover.js'

	import PollInformationIcon from 'vue-material-design-icons/InformationOutline.vue'
	
	import { ActionToggleSidebar } from '../Actions/index.js'
	import PollInformation from '../Poll/PollInformation.vue'
	import UserMenu from '../User/UserMenu.vue'
	import ExportPoll from '../Export/ExportPoll.vue'
	import { usePollStore } from '../../stores/poll.ts'
	import { useSessionStore } from '../../stores/session.ts'


	const route = useRoute()
	const pollStore = usePollStore()
	const sessionStore = useSessionStore()
	const caption = t('polls', 'Poll informations')

	const showUserMenu = computed(() => (route.name !== 'publicVote' || pollStore.permissions.vote || pollStore.permissions.subscribe))

	onBeforeUnmount(() => {
		pollStore.$reset()
	})

</script>

<template>
	<div class="poll-header-buttons">
		<UserMenu v-if="showUserMenu" />
		<NcPopover :focus-trap="false">
			<template #trigger>
				<NcButton :title="caption"
					:aria-label="caption"
					:type="ButtonType.Tertiary">
					<template #icon>
						<PollInformationIcon />
					</template>
				</NcButton>
			</template>
			<PollInformation />
		</NcPopover>
		<ExportPoll v-if="sessionStore.appPermissions.pollDownload" />
		<ActionToggleSidebar v-if="pollStore.permissions.edit || pollStore.permissions.comment" />
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
