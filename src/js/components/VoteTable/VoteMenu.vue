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
	<div class="vote_menu">
		<ActionSortOptions button-mode />
		<ActionChangeView />
	</div>
</template>

<script>
import ActionSortOptions from '../Actions/ActionSortOptions'
import ActionChangeView from '../Actions/ActionChangeView'

export default {
	name: 'VoteMenu',

	components: {
		ActionChangeView,
		ActionSortOptions,
	},
}
</script>

<style lang="scss">
.vote_menu {
	display: flex;
	justify-content: flex-end;
	flex: 0;
	background-color: var(--color-background-darker);
	border-radius: var(--border-radius-pill);
	margin: 8px;
}

</style>
