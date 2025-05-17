<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import moment from '@nextcloud/moment'
import { t } from '@nextcloud/l10n'

import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcButton from '@nextcloud/vue/components/NcButton'

import { InputDiv } from '../Base/index.ts'
import { useOptionsStore, Option, Sequence } from '../../stores/options.ts'
import { dateTimeUnitsKeyed } from '../../constants/dateUnits.ts'

const { option } = defineProps<{ option: Option }>()

const optionsStore = useOptionsStore()

const dateTimeOptions = Object.entries(dateTimeUnitsKeyed).map(([key, value]) => ({
	id: key,
	name: value.name,
}))

const emit = defineEmits(['close'])

const sequence = ref<Sequence>({
	unit: dateTimeUnitsKeyed.week,
	stepWidth: 1,
	repetitions: 1,
})

const dateBaseOptionString = computed(() =>
	moment.unix(option.timestamp).format('LLLL'),
)

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
			:input-label="t('polls', 'Step unit')"
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
				use-num-modifiers />

			<InputDiv
				v-model="sequence.repetitions"
				:label="t('polls', 'Amount')"
				type="number"
				inputmode="numeric"
				use-num-modifiers />
		</div>

		<div class="modal__buttons">
			<NcButton @click="emit('close')">
				<template #default>
					{{ t('polls', 'Cancel') }}
				</template>
			</NcButton>

			<NcButton :variant="'primary'" @click="createSequence()">
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
