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
	<div class="header_bar">
		<div class="header_bar_top">
			<div class="bar_top_left">
				<div :class="['header_title', { 'clamped': clamped }]" @click="toggleClamp()">
					<slot name="title" />
				</div>
				<div class="bar_top_left_sub">
					<slot name="sub" />
				</div>
			</div>
			<div class="bar_top_right">
				<slot name="right" />
			</div>
		</div>
		<div class="header_bar_bottom">
			<slot name="default" />
		</div>
	</div>
</template>

<script>
export default {
	name: 'HeaderBar',
	data() {
		return {
			clamped: true,
		}
	},
	methods: {
		toggleClamp() {
			this.clamped = !this.clamped
		},
	},
}
</script>

<style lang="scss">
.page--scrolled .header_bar_bottom {
	display: none;
}

.header_bar {
	.header_bar_top {
		display: flex;
		flex-wrap: wrap-reverse;
		justify-content: flex-end;
		gap:8px;
		min-height: 3em;

		.bar_top_left {
			display: flex;
			flex-direction: column;
			flex: 1 180px;
			justify-content: center;
		}

		.header_title {
			font-weight: bold;
			font-size: 1em;
			line-height: 1.5em;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			&.clamped {
				-webkit-line-clamp: 2;
				line-clamp: 2;
				-webkit-box-orient: vertical;
			}
		}
		.sub {
			display: flex;
			flex-wrap: wrap;
		}
		.header_bar_bottom {
			display: flex;
			margin-bottom: 16px;
		}
	}

	[class*="bar_"] {
		flex: 0;
	}
}
</style>
