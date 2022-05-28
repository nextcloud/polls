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
			viewBox="0 0 4.2333 4.2333">
			<g transform="translate(0 -292.77)">
				<g transform="translate(0 -.52917)">
					<path transform="matrix(.26458 0 0 .26458 0 293.3)"
						d="m5.1602 1.9746v2.205h-4.5937v7.6836h2.8047v2.3262h10.291v-4.5371h1.7891v-7.6777h-9.9043zm0.75195 0.74609h8.7871v6.1855h-1.0371v-2.3945h-2.8047v-2.332h-4.9453zm-4.5938 2.2109h8.793v1.5801h-6.7402v4.6054h-2.0527v-6.1855zm2.8066 2.3262h8.793v6.1855h-8.793z"
						stroke="fillColor"
						stroke-width=".4">
						<title v-if="title">{{ title }}</title>
					</path>
				</g>
			</g>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'ProjectsAppIcon',
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
}
</script>
