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
	<div class="loading-overlay">
		<span class="icon-loading" />
	</div>
</template>

<script>
export default {
	name: 'LoadingOverlay',
}
</script>

<style lang="scss">
.loading-overlay {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	background: var(--color-main-background);
	opacity: 0.9;
	z-index: 9999;

	.icon-loading {
		position: fixed;
		left: 50%;
		top: 50%;
		margin-left: -35px;
		margin-top: -10px;

		&::after {
			border: 10px solid var(--color-loading-light);
			border-top-color: var(--color-primary-element);
			height: 70px;
			width: 70px;
		}
	}
}
</style>
