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
	<div class="checkbox-div">
		<input :id="id"
			:checked="value"
			type="checkbox"
			class="checkbox"
			@click="$emit('input', $event.target.checked)">
		<label :for="id">
			<slot name="before" />
			{{ label }}
		</label>
	</div>
</template>

<script>
const RandId = () => {
	return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2, 10)
}

export default {
	name: 'CheckBoxDiv',
	props: {
		label: {
			type: String,
			required: true,
		},
		value: {
			type: [Boolean, Number],
			required: true,
		},
		id: {
			type: String,
			default: () => 'cb-' + RandId(),
		},
	},
}
</script>
