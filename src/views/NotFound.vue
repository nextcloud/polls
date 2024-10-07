<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { t } from '@nextcloud/l10n'

	import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
	import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
	
	import SearchIcon from 'vue-material-design-icons/Magnify.vue'

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
