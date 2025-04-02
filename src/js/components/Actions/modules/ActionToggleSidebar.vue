<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action toggle-sidebar">
		<NcButton variant="tertiary"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<SidebarIcon />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import SidebarIcon from 'vue-material-design-icons/TextAccount.vue' // view-comfy-outline

export default {
	name: 'ActionToggleSidebar',

	components: {
		SidebarIcon,
		NcButton,
	},

	data() {
		return {
			caption: t('polls', 'Toggle Sidebar'),
		}
	},

	methods: {
		clickAction() {
			emit('polls:sidebar:toggle')
		},
	},
}
</script>
