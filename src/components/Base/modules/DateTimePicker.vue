<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { DateTime } from 'luxon'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'

import ChevronLeftIcon from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import { PropType } from 'vue'

defineOptions({
	inheritAttrs: false,
})

const model = defineModel({
	required: true,
	type: Object as PropType<Date>,
})

const props = defineProps({
	useDayButtons: {
		type: Boolean,
		default: false,
	},
})

function previousDay() {
	if (model.value) {
		model.value = DateTime.fromJSDate(model.value).minus({ days: 1 }).toJSDate()
	}
}

function nextDay() {
	if (model.value) {
		model.value = DateTime.fromJSDate(model.value).plus({ days: 1 }).toJSDate()
	}
}
</script>

<template>
	<div class="date-time-picker">
		<NcButton
			v-if="props.useDayButtons"
			:title="t('polls', 'Previous day')"
			:variant="'tertiary-no-background'"
			@click="previousDay">
			<template #icon>
				<ChevronLeftIcon />
			</template>
		</NcButton>
		<NcDateTimePickerNative v-model="model" v-bind="$attrs" />
		<NcButton
			v-if="props.useDayButtons"
			:title="t('polls', 'Next day')"
			:variant="'tertiary-no-background'"
			@click="nextDay">
			<template #icon>
				<ChevronRightIcon />
			</template>
		</NcButton>
	</div>
</template>

<style lang="scss" scoped>
.date-time-picker {
	display: flex;
	flex-wrap: wrap;
	align-items: end;
}
</style>
