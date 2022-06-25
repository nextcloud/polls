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
		<div class="config-box__header">
			<slot name="icon" />
			<div v-tooltip.auto="info" :class="['config-box__title', iconClassComputed, {indented: indented}]">
				{{ title }}
				<div v-if="info" class="icon-info" />
			</div>
			<slot name="actions" />
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
			default: null,
		},
		info: {
			type: String,
			default: '',
		},
		indented: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		hasIconSlot() {
			return !!this.$slots.icon
		},

		iconClassComputed() {
			// presence of an icon slot overrides the icon class
			return this.hasIconSlot ? null : this.iconClass
		},
	},
}

</script>

<style lang="scss">
.config-box__header {
	display: flex;
	align-content: center;
	align-items: center;
	gap: 5px;
	margin: 8px 0 8px 0;
}

.config-box {
	display: flex;
	flex-direction: column;
	padding: 8px 0;
	.icon-container {
		width: 20px;
	}

	.config-box__title {
		display: flex;
		flex: 1;
		opacity: 0.7;
		font-weight: bold;
		margin: 0;

		&[class*='icon-'] {
			padding-left: 24px;
		}

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
			width: 100%;
		}
	}
}

.indented {
	margin-left: 24px !important;
}
</style>
