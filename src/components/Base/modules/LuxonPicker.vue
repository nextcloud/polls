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
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const { useDayButtons = false, label = '' } = defineProps<{
	useDayButtons?: boolean
	label: string
}>()

const model = defineModel<DateTime>({ required: true })

const jsDateTime = computed({
	get() {
		return model.value.toJSDate()
	},
	set(jsDateTime) {
		model.value = DateTime.fromJSDate(jsDateTime)
	},
})

function previousDay() {
	model.value = model.value.minus({ days: 1 })
}

function nextDay() {
	model.value = model.value.plus({ days: 1 })
}
</script>

<template>
	<div class="luxon-picker">
		<div v-if="label">{{ label }}</div>
		<div class="date-time-picker">
			<NcButton
				v-if="useDayButtons"
				:title="t('polls', 'Previous day')"
				:variant="'tertiary-no-background'"
				@click="previousDay">
				<template #icon>
					<ChevronLeftIcon />
				</template>
			</NcButton>
			<NcDateTimePickerNative v-model="jsDateTime" v-bind="$attrs" />
			<NcButton
				v-if="useDayButtons"
				:title="t('polls', 'Next day')"
				:variant="'tertiary-no-background'"
				@click="nextDay">
				<template #icon>
					<ChevronRightIcon />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss" scoped>
.luxon-picker {
	display: flex;
	flex-direction: column;
}
.date-time-picker {
	display: flex;
	align-items: end;
}
</style>
