<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	import { emit } from '@nextcloud/event-bus'
	import { t } from '@nextcloud/l10n'

	const caption = t('polls', 'Add some!')

	function clickAction() {
		emit('polls:sidebar:toggle', { open: true, activeTab: 'options' })
	}

</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton :type="ButtonType.Primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
	</div>
</template>
