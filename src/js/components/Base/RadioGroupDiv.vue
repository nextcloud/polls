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
	<div class="radio-group-div">
		<CheckboxRadioSwitch v-for="(option, index) in options"
			:key="option.value"
			:name="id + '_' + index"
			:checked="option.value === value"
			type="radio"
			@update:checked="$emit('input', option.value)">
			{{ option.label }}
		</CheckboxRadioSwitch>
	</div>
</template>

<script>
import { CheckboxRadioSwitch } from '@nextcloud/vue'

const RandId = () => Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2, 10)

export default {
	name: 'RadioGroupDiv',

	components: {
		CheckboxRadioSwitch,
	},

	props: {
		id: {
			type: String,
			default: () => 'rg-' + RandId(),
		},
		options: {
			type: Array,
			required: true,
		},
		value: {
			type: String,
			default: null,
		},
	},
}
</script>
