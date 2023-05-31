<!--
  - @copyright Copyright (c) 2022 René Gieling <github@dartcafe.de>
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
	<span :aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon shield-crown-outline"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 24 24">
			<path d="M12 1L21 5V11C21 16.55 17.16 21.74 12 23C6.84 21.74 3 16.55 3 11V5L12 1M12 3.18L5 6.3V11.22C5 15.54 8.25 20 12 21C15.75 20 19 15.54 19 11.22V6.3L12 3.18M16 14V15.5L16 15.59C15.96 15.81 15.78 15.96 15.53 16L15.43 16H8.57L8.47 16C8.22 15.96 8.04 15.81 8 15.59L8 15.5V14H16M17 8L16 13H8L7 8L7 8L9.67 10.67L12 8.34L14.33 10.67L17 8L17 8Z">
				<title v-if="title">{{ title }}</title>
			</path>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'ShieldCrownOutline',
	props: {
		title: {
			type: String,
			default: 'Admin Access',
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
		size: {
			type: Number,
			default: 24,
		},
	},

	emits: {
		click: null,
	},
}
</script>
