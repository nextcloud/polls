<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, PropType } from 'vue'
	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

	export type CheckboxOption = {
		value: string
		label: string
	}

	const RandId = () => Math.random().toString(36).replace(/[^a-z]+/g, '').slice(2, 12)

	const props = defineProps({
		id: {
			type: String,
			default: null,
		},
		options: {
			type: Array as PropType<CheckboxOption[]>,
			required: true,
		},
	})

	const model = defineModel({
		type: String,
		default: null,
	})

	const emit = defineEmits(['update'])

	const elementId = computed(() => props.id ?? `rg-${RandId()}`)

</script>

<template>
	<div class="radio-group-div">
		<NcCheckboxRadioSwitch v-for="(option, index) in options"
			:key="option.value"
			v-model="model"
			:value="option.value"
			:name="elementId + index"
			type="radio"
			@update:model-value="emit('update')">
			{{ option.label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>
