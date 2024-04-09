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
	<div class="flex_settings">
		<slot name="default" />
	</div>
</template>

<script>

export default {
	name: 'FlexSettings',
}
</script>

<style lang="scss">
.flex_settings {
	display: flex;
	flex-wrap: wrap;
	align-items: stretch;

	.settings-section {
		flex: 1 0 480px;
		margin-bottom: 0;
		border-bottom: 1px solid var(--color-border);
	}
}

.settings-description {
	margin-top: -.2em;
	margin-bottom: 1em;
	opacity: .7;
}

.user_settings {
	padding-top: 8px;

	textarea {
		width: 99%;
		resize: vertical;
		height: 230px;
	}
}

.settings_details {
	padding-bottom: 16px;
	margin-left: 36px;
}

</style>
