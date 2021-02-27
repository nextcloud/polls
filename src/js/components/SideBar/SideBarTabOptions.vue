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
		<ConfigBox v-if="!isOwner" :title="t('polls', 'As an admin you may edit this poll')" icon-class="icon-checkmark" />
		<OptionsDateAdd v-if="pollType === 'datePoll' && !pollIsClosed" />
		<OptionsDateShift v-if="pollType === 'datePoll' && countOptions && !pollIsClosed" />
		<OptionsDate v-if="pollType === 'datePoll'" />

		<OptionsTextAdd v-if="pollType === 'textPoll' && !pollIsClosed" />
		<OptionsText v-if="pollType === 'textPoll'" />
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import OptionsDate from '../Options/OptionsDate'
import OptionsDateAdd from '../Options/OptionsDateAdd'
import OptionsDateShift from '../Options/OptionsDateShift'
import OptionsText from '../Options/OptionsText'
import OptionsTextAdd from '../Options/OptionsTextAdd'

export default {
	name: 'SideBarTabOptions',

	components: {
		ConfigBox,
		OptionsDate,
		OptionsDateAdd,
		OptionsDateShift,
		OptionsText,
		OptionsTextAdd,
	},

	computed: {
		...mapGetters({
			pollIsClosed: 'poll/closed',
			countOptions: 'options/count',
		}),
		...mapState({
			pollType: state => state.poll.type,
			isOwner: state => state.poll.acl.isOwner,
		}),

	},
}
</script>
