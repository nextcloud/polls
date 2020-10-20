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
	<div>
		<ConfigBox v-if="!acl.isOwner" :title="t('polls', 'As an admin you may edit this poll')" icon-class="icon-checkmark" />
		<SideBarTabOptionsDate v-if="poll.type === 'datePoll'" />
		<SideBarTabOptionsText v-if="poll.type === 'textPoll'" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import SideBarTabOptionsDate from './SideBarTabOptionsDate'
import SideBarTabOptionsText from './SideBarTabOptionsText'

export default {
	name: 'SideBarTabOptions',

	components: {
		ConfigBox,
		SideBarTabOptionsDate,
		SideBarTabOptionsText,
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
		}),

	},
}
</script>
