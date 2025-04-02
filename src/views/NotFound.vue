<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

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
