<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { randomId } from '../../../helpers/modules/randomId.ts'

export type CheckboxOption = {
	value: string
	label: string
}

interface Props {
	id?: string
	options: CheckboxOption[]
}

const model = defineModel<string>({ required: true })
const { id = `rg-${randomId()}`, options } = defineProps<Props>()
const emit = defineEmits(['update'])

const elementId = computed(() => id)
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
			@update:modelValue="emit('update')">
			{{ option.label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>
