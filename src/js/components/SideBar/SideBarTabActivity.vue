<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="comments">
		<Activities v-if="!showEmptyContent" />
		<NcEmptyContent v-else :name="t('polls', 'No activity')">
			<template #icon>
				<ActivityIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import Activities from '../Activity/Activities.vue'
import { NcEmptyContent } from '@nextcloud/vue'
import { mapState } from 'vuex'
import ActivityIcon from 'vue-material-design-icons/LightningBolt.vue'

export default {
	name: 'SideBarTabActivity',
	components: {
		ActivityIcon,
		Activities,
		NcEmptyContent,
	},

	computed: {
		...mapState({
			activities: (state) => state.activity.list,
		}),

		showEmptyContent() {
			return this.activities.length === 0
		},

	},

}
</script>
