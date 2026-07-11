<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Option, Sequence } from '@/stores/options.types'

import { t } from '@nextcloud/l10n'
import { DateTime } from 'luxon'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import InputDiv from '../Base/modules/InputDiv.vue'
import { dateTimeUnitsKeyed } from '@/helpers/modules/dateHelpers'
import { useOptionsStore } from '@/stores/options'

const { option } = defineProps<{ option: Option }>()

const emit = defineEmits(['close'])

const optionsStore = useOptionsStore()

const dateTimeOptions = Object.entries(dateTimeUnitsKeyed).map(([key, value]) => ({
	id: key,
	name: value.name,
}))

const sequence = ref<Sequence>({
	unit: dateTimeUnitsKeyed.week,
	stepWidth: 1,
	repetitions: 1,
})

const dateBaseOptionString = computed(() =>
	option.getDateTime().toLocaleString(DateTime.DATETIME_FULL),)

/**
 *
 */
function createSequence() {
	optionsStore.sequence({
		option,
		sequence: sequence.value,
	})
	emit('close')
}
</script>

<template>
	<div class="option-clone-date">
		<h2>{{ t('polls', 'Clone to option sequence') }}</h2>
		<p>
			{{
				t(
					'polls',
					'Create a sequence of date options starting with {dateOption}.',
					{ dateOption: dateBaseOptionString },
				)
			}}
		</p>

		<NcSelect
			v-model="sequence.unit"
			:inputLabel="t('polls', 'Step unit')"
			:clearable="false"
			:filterable="false"
			:options="dateTimeOptions"
			label="name" />

		<div class="sideways">
			<InputDiv
				v-model="sequence.stepWidth"
				:label="t('polls', 'Step width')"
				type="number"
				inputmode="numeric"
				useNumModifiers />

			<InputDiv
				v-model="sequence.repetitions"
				:label="t('polls', 'Amount')"
				type="number"
				inputmode="numeric"
				useNumModifiers />
		</div>

		<div class="modal__buttons">
			<NcButton @click="emit('close')">
				<template #default>
					{{ t('polls', 'Cancel') }}
				</template>
			</NcButton>

			<NcButton variant="primary" @click="createSequence()">
				<template #default>
					{{ t('polls', 'OK') }}
				</template>
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss">
.sideways {
	display: flex;
	column-gap: 48px;
	flex-wrap: wrap;
}

.option-clone-date {
	& > .v-select,
	& > .sideways {
		margin-top: 8px;
	}
}
</style>
