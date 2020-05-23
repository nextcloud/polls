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
	<Component :is="tag" :class="['badge', icon, { withIcon: withIcon } ]">
		{{ title }}
	</Component>
</template>

<script>
export default {
	name: 'Badge',
	props: {
		title: {
			type: String,
			default: '',
		},
		icon: {
			type: String,
			default: '',
		},
		tag: {
			type: String,
			default: 'span',
		},
	},

	computed: {
		withIcon() {
			return Boolean(this.icon)
		},
	},
}
</script>

<style lang="scss" scoped>
	.badge {
		border: solid 1px;
		border-radius: var(--border-radius);
		padding: 1px 4px;
		margin: 0 4px;
		font-size: 60%;
		text-align: center;

		&.withIcon {
			padding-left: 26px;
			background-position: 4px center;
		}

		&.error {
			border-color: var(--color-error);
			background-color: var(--color-error);
			color: var(--color-primary-text);
		}

		&.success {
			border-color: var(--color-success);
			background-color: var(--color-success);
			color: var(--color-primary-text);
		}
	}

</style>
