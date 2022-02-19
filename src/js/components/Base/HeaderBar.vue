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
	<Component :is="tag" class="header_bar">
		<div class="header_bar_top">
			<div id="bar_top_left" class="bar_top_left">
				<slot name="left" />
			</div>
			<div class="bar_top_right">
				<slot name="right" />

			</div>
		</div>
		<div class="header_bar_bottom">
			<slot name="bottom" />
		</div>
	</Component>
</template>

<script>
import Spacer from './Spacer'

export default {
	name: 'HeaderBar',
	components: {
		Spacer,
	},

	props: {
		tag: {
			type: String,
			default: 'div',
		},
	},
}

</script>

<style lang="scss">
.header_bar {
	.header_bar_top {
		display: flex;
		flex-wrap: wrap-reverse;
		justify-content: flex-end;
		.bar_top_left {
			font-weight: bold;
			font-size: 1.3em;
			line-height: 1.5em;
			flex: 1 180px;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			line-clamp: 2;
			-webkit-box-orient: vertical;
			padding-right: 8px;
		}
	}

	.header_bar_bottom {
		display: flex;
		margin-bottom: 16px;
	}

	[class*='bar_'] {
		flex: 0;
	}
}

</style>
