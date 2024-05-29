<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent>
		<NcEmptyContent :name="t('polls', '404 - poll not found')"
			:description="t('polls', 'Enter a poll or start a new one.')">
			<template #icon>
				<SearchIcon />
			</template>
		</NcEmptyContent>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent } from '@nextcloud/vue'
import SearchIcon from 'vue-material-design-icons/Magnify.vue'

export default {
	name: 'NotFound',
	components: {
		SearchIcon,
		NcAppContent,
		NcEmptyContent,
	},
}
</script>
