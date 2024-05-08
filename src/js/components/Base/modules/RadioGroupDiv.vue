<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="radio-group-div">
		<NcCheckboxRadioSwitch v-for="(option, index) in options"
			:key="option.value"
			:checked.sync="selectedValue"
			:value="option.value"
			:name="id + '_' + index"
			type="radio"
			@update:checked="$emit('input', option.value)">
			{{ option.label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

const RandId = () => Math.random().toString(36).replace(/[^a-z]+/g, '').slice(2, 12)

export default {
	name: 'RadioGroupDiv',

	components: {
		NcCheckboxRadioSwitch,
	},

	props: {
		id: {
			type: String,
			default: () => `rg-${RandId()}`,
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

	computed: {
		selectedValue: {
			get() {
				return this.value
			},
			set(value) {
				this.$emit('input', value)
			},
		},
	},
}
</script>
