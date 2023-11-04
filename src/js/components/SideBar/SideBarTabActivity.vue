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
			acl: (state) => state.poll.acl,
			activities: (state) => state.activity.list,
		}),

		showEmptyContent() {
			return this.activities.length === 0
		},

	},

}
</script>
