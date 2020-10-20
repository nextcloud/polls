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
	<div class="input-div">
		<input ref="input"
			:value="value"
			:placeholder="placeholder"
			class="input"
			@keyup.enter="$emit('input', $event.target.value)">
		<button class="submit icon-confirm"
			@click="$emit('input', $refs.input.value)" />
	</div>
</template>

<script>
export default {
	name: 'InputDiv',
	props: {
		value: {
			type: String,
			required: true,
		},
		placeholder: {
			type: String,
			default: '',
		},
	},
}
</script>

<style lang="scss" scoped>

	.input-div {
		display: flex;
	}

	.input {
		flex: 1;
		&:empty:before {
			color: grey;
		}
	}

	.submit {
		width: 30px;
		background-color: transparent;
		border: none;
		opacity: 0.3;
		cursor: pointer;
	}

</style>
