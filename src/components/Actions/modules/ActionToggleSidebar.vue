<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'

import SidebarIcon from 'vue-material-design-icons/TextAccount.vue' // view-comfy-outline
import { Event } from '../../../Types'

const caption = t('polls', 'Toggle sidebar')
/**
 *
 */
function clickAction() {
	emit(Event.SidebarToggle, null)
}
</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton
			:variant="'tertiary'"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<SidebarIcon />
			</template>
		</NcButton>
	</div>
</template>
