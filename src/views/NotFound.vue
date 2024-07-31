<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { NcAppContent, NcEmptyContent } from '@nextcloud/vue'
	import SearchIcon from 'vue-material-design-icons/Magnify.vue'
	import { t } from '@nextcloud/l10n'

	const emptyContentProps = {
		name: t('polls', '404 - poll not found'),
		description: t('polls', 'Enter a poll or start a new one.'),
	}
</script>

<template>
	<NcAppContent>
		<NcEmptyContent v-bind="emptyContentProps">
			<template #icon>
				<SearchIcon />
			</template>
		</NcEmptyContent>
	</NcAppContent>
</template>
