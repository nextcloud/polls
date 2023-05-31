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
		class="material-design-icon activity-app-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 32 32">
			<path d="m16 1-10 18h11l-1 12 10-18h-11z" :fill="fillColor">
				<title v-if="title">{{ title }}</title>
			</path>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'ActivityAppIcon',
	props: {
		title: {
			type: String,
			default: 'Activity',
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
