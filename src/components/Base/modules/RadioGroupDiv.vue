<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="radio-group-div">
		<NcCheckboxRadioSwitch v-for="(option, index) in options"
			:key="option.value"
			v-model="model"
			:value="option.value"
			:name="elementId + index"
			type="radio"
			@update:model-value="$emit('update')">
			{{ option.label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script setup>
import { computed } from 'vue'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

const RandId = () => Math.random().toString(36).replace(/[^a-z]+/g, '').slice(2, 12)

const props = defineProps({
	id: {
		type: String,
		default: null,
	},
	options: {
		type: Array,
		required: true,
	},
})

const model = defineModel({
	type: String,
	default: null,
})

const elementId = computed(() => props.id ?? `rg-${RandId()}`)

</script>
