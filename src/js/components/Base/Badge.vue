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

<template lang="html">
	<Component :is="tag" class="badge">
		<div>
			<slot name="icon" />
		</div>
		<span>
			<slot />
		</span>
	</Component>
</template>

<script>
export default {
	name: 'Badge',
	props: {
		tag: {
			type: String,
			default: 'span',
		},
	},
}
</script>

<style lang="scss">
	.badge {
		display: flex;
		align-items: center;
		gap: 5px;
		border-radius: var(--border-radius);
		padding: 5px;
		margin: 8px 4px;
		text-align: center;
		line-height: 1.1em;
		overflow: hidden;
		text-overflow: ellipsis;
		font-size: 0.9em;

		h2 & {
			font-size: 0.6em;
		}

		&.error {
			border-color: var(--color-error);
			background-color: var(--color-error);
			color: var(--color-primary-text) !important;
		}

		&.success {
			border-color: var(--color-success);
			background-color: var(--color-success);
			color: var(--color-primary-text) !important;
		}
	}

</style>
