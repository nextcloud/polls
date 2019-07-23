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
	<div id="app-polls">
		<router-view />
	</div>
</template>

<script>
export default {
	name: 'App'
}
</script>

<style  lang="scss">
#app-polls {
	width: 100%;
	display: flex;
}

#app-content {
    width: 100%;
    display: flex;
	&>.main-container {
		flex: 1;
		padding: 0 8px;
	}

	input.hasTimepicker {
        width: 75px;
    }

	.label {
		border: solid 1px;
		border-radius: var(--border-radius);
		padding: 1px 4px;
		margin: 0 4px;
		font-size: 60%;
		text-align: center;
		&.error {
			border-color: var(--color-error);
			background-color: var(--color-error);
			color: var(--color-primary-text);
		}
	}
}
</style>
