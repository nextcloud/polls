<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<TransitionGroup name="fade"
		class="activities"
		tag="ul">
		<ActivityItem v-for="(item) in activities"
			:key="item.activity_id"
			:activity="item"
			tag="li" />
	</TransitionGroup>
</template>

<script>
import ActivityItem from './ActivityItem.vue'
import { mapState } from 'vuex'

export default {
	name: 'Activities',
	components: {
		ActivityItem,
	},

	computed: {
		...mapState({
			activities: (state) => state.activity.list,
		}),

	},
}
</script>
