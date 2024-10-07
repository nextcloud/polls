<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	import { emit } from '@nextcloud/event-bus'
	import SidebarIcon from 'vue-material-design-icons/TextAccount.vue' // view-comfy-outline
	import { t } from '@nextcloud/l10n'

	const caption = t('polls', 'Toggle Sidebar')
	function clickAction() {
		emit('polls:sidebar:toggle', null)
	}

</script>


<template>
	<div class="action toggle-sidebar">
		<NcButton :type="ButtonType.Tertiary"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<SidebarIcon />
			</template>
		</NcButton>
	</div>
</template>
