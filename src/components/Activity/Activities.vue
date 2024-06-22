<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<TransitionGroup name="fade"
		class="activities"
		tag="ul">
		<ActivityItem v-for="(item) in activityStore.list"
			:key="item.activity_id"
			:activity="item"
			tag="li" />
	</TransitionGroup>
</template>

<script>
import { mapStores } from 'pinia'

import ActivityItem from './ActivityItem.vue'
import { useActivityStore } from '../../stores/activity.ts';

export default {
	name: 'Activities',
	components: {
		ActivityItem,
	},

	computed: {
		...mapStores(useActivityStore),
	},
}
</script>
