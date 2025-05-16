<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import { Event } from '../../../Types'

const caption = t('polls', 'Add some!')

/**
 *
 */
function clickAction() {
	emit(Event.SidebarToggle, {
		open: true,
		activeTab: 'options',
	})
}
</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton
			:variant="'primary'"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
	</div>
</template>
