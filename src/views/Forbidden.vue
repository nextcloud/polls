<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import CancelIcon from 'vue-material-design-icons/Cancel.vue'

const emptyContentProps = {
	name: t('polls', '403 - You are not allowed to access this poll'),
	description: t('polls', 'Contact the owner, if you think that this is wrong.'),
}
</script>

<template>
	<NcAppContent>
		<NcEmptyContent v-bind="emptyContentProps">
			<template #icon>
				<CancelIcon />
			</template>
		</NcEmptyContent>
	</NcAppContent>
</template>
