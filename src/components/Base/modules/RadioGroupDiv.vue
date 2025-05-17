<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

export type CheckboxOption = {
	value: string
	label: string
}

interface Props {
	id?: string
	options: CheckboxOption[]
}

const { id, options } = defineProps<Props>()
const model = defineModel<string>({ required: true })

const RandId = () =>
	Math.random()
		.toString(36)
		.replace(/[^a-z]+/g, '')
		.slice(2, 12)

const emit = defineEmits(['update'])

const elementId = computed(() => id ?? `rg-${RandId()}`)
</script>

<template>
	<div class="radio-group-div">
		<NcCheckboxRadioSwitch
			v-for="(option, index) in options"
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
