<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'

import ChevronLeftIcon from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'

const model = defineModel({
	required: true,
	type: Object,
})

const props = defineProps({
	useDayButtons: {
		type: Boolean,
		default: false,
	},
})

function previousDay() {
	if (model.value) {
		const date = moment(model.value).subtract(1, 'day')
		model.value = date.toDate()
	}
}

function nextDay() {
	if (model.value) {
		const date = moment(model.value).add(1, 'day')
		model.value = date.toDate()
	}
}
</script>

<template>
	<div class="date-time-picker">
		<NcButton
			v-if="props.useDayButtons"
			:title="t('polls', 'Previous day')"
			:type="ButtonType.TertiaryNoBackground"
			@click="previousDay">
			<template #icon>
				<ChevronLeftIcon />
			</template>
		</NcButton>
		<NcDateTimePickerNative
			v-model="model"
			v-bind="$attrs" />
		<NcButton
			v-if="props.useDayButtons"
			:title="t('polls', 'Next day')"
			:type="ButtonType.TertiaryNoBackground"
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
	column-gap: 0.25rem;
	align-items: end;
}
</style>
