<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
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
