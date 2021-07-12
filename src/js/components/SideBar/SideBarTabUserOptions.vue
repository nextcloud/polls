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
		<ConfigBox :title="t('polls', 'User Menu')" icon-class="icon-settings">
			<UserMenu />
		</ConfigBox>
		<ConfigBox :title="t('polls', 'Poll informations')" icon-class="icon-info">
			<PollInformation />
		</ConfigBox>
	</div>
</template>

<script>
import ConfigBox from '../Base/ConfigBox'
import PollInformation from '../Poll/PollInformation'
import UserMenu from '../User/UserMenu'

export default {
	name: 'SideBarTabUserOptions',

	components: {
		ConfigBox,
		PollInformation,
		UserMenu,
	},
}
</script>
