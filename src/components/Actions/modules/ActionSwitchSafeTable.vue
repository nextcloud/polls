<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { t } from '@nextcloud/l10n'
	import { usePollStore } from '../../../stores/poll.ts'

	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'

	const pollStore = usePollStore()
	const caption = t('polls', 'Reveal them')

</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton :type="ButtonType.Primary"
			:aria-label="caption"
			@click="pollStore.switchSafeTable()">
			{{ caption }}
		</NcButton>
	</div>
</template>
