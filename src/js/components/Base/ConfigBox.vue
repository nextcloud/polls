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
	<div class="config-box">
		<div v-tooltip.auto="info" class="config-box__title" :class="iconClass">
			{{ title }}
			<div v-if="info" class="icon-info" />
		</div>
		<div class="config-box__container">
			<slot />
		</div>
	</div>
</template>

<script>
export default {
	name: 'ConfigBox',
	props: {
		title: {
			type: String,
			default: '',
		},
		iconClass: {
			type: String,
			default: '',
		},
		info: {
			type: String,
			default: '',
		},
	},
}
</script>

<style lang="scss">
.config-box {
	padding: 8px 0;
}

.config-box__title {
	display: flex;
	background-position: 0 4px;
	opacity: 0.7;
	font-weight: bold;
	margin: 8px 0 4px 0;
	padding-left: 24px;

	.icon-info {
		opacity: 0.7;
		width: 32px;
	}
}

.config-box__container{
	display: flex;
	flex-direction: column;
	padding-left: 24px;

	label {
		margin: 4px 0;
	}

	input, textarea {
		width: auto;
	}
}

.indented {
	margin-left: 24px !important;
}

</style>
