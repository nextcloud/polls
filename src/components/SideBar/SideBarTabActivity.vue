<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="comments">
		<Activities v-if="!showEmptyContent" />
		<NcEmptyContent v-else v-bind="emptyContentProps">
			<template #icon>
				<ActivityIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import Activities from '../Activity/Activities.vue'
import { NcEmptyContent } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import ActivityIcon from 'vue-material-design-icons/LightningBolt.vue'
import { t } from '@nextcloud/l10n'
import { useActivityStore } from '../../stores/activity.ts'

export default {
	name: 'SideBarTabActivity',
	components: {
		ActivityIcon,
		Activities,
		NcEmptyContent,
	},

	data() {
		return {
			emptyContentProps: {
				name: t('polls', 'No comments'),
			}
		}
	},

	computed: {
		...mapStores(useActivityStore),

		showEmptyContent() {
			return this.activityStore.list.length === 0
		},

	},

}
</script>
