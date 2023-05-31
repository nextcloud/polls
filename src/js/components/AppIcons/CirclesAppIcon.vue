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
		class="material-design-icon circles-app-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 57 57">
			<path d="M7.1 28.5A21.4 21.4 0 0 1 28.5 7.1m10.7 40A21.4 21.4 0 0 1 10 39M39.2 10A21.4 21.4 0 0 1 47 39.2"
				fill="none"
				:stroke="fillColor"
				stroke-width="4"
				stroke-linecap="round"
				stroke-linejoin="round" />
			<circle cx="28.5"
				cy="7.1"
				r="6.5"
				:fill="fillColor" />
			<circle cx="39.2"
				cy="-10"
				r="6.5"
				transform="rotate(90)"
				:fill="fillColor" />
			<circle cx="39.2"
				cy="-47"
				r="6.5"
				transform="rotate(90)"
				:fill="fillColor" />
			<title v-if="title">{{ title }}</title>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'CirclesAppIcon',
	props: {
		title: {
			type: String,
			default: 'Circles',
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
