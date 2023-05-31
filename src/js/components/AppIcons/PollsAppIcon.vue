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
		class="material-design-icon polls-app-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 32 32">
			<g :fill="fillColor">
				<rect y="2"
					x="3"
					height="26"
					width="7" />
				<rect y="12"
					x="12"
					height="16"
					width="7" />
				<rect y="8"
					x="21"
					height="20"
					width="7" />
				<title v-if="title">{{ title }}</title>
			</g>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'PollsAppIcon',
	props: {
		title: {
			type: String,
			default: 'Polls',
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
