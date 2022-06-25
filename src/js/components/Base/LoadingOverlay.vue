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
		<Spinner :size="70" />
	</div>
</template>

<script>
import Spinner from '../AppIcons/Spinner.vue'

export default {
	name: 'LoadingOverlay',
	components: {
		Spinner,
	},
}
</script>

<style lang="scss">
.loading-overlay {
	position: absolute;
	left: 0;
	top: 0;
	width: 100vw;
	height: 100vh;
	background: var(--color-main-background);
	opacity: 0.9;
	z-index: 9999;

	.spinner {
		position: fixed;
		left: 50%;
		top: 50%;
	}
}
</style>
