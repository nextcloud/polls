<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

	import SubmitIcon from 'vue-material-design-icons/ArrowRight.vue'

	import { InputDiv } from '../Base/index.js'
	import { dateUnits, DateUnitValue } from '../../constants/dateUnits.ts'
	import { useOptionsStore, Shift } from '../../stores/options.ts'

	const optionsStore = useOptionsStore()

	const shift = ref<Shift>({
		step: 1,
		unit: { name: t('polls', 'Week'), value: DateUnitValue.Week },
	})

	function shiftDates(shift: Shift) {
		optionsStore.shift({ shift })
	}

</script>

<template>
	<div>
		<div v-if="optionsStore.proposalsExist">
			{{ t('polls', 'Shifting dates is disabled to prevent shifting of proposals of other participants.') }}
		</div>
		<div v-else class="select-unit">
			<InputDiv v-model="shift.step" :label="t('polls', 'Step width')" use-num-modifiers />
			<NcSelect v-model="shift.unit"
				:input-label="t('polls', 'Step unit')"
				:clearable="false"
				:filterable="false"
				:options="dateUnits"
				label="name" />
			<NcButton class="submit"
				:aria-label="t('polls', 'Submit')"
				:type="ButtonType.Tertiary"
				@click="shiftDates(shift)">
				<template #icon>
					<SubmitIcon />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss">
.select-unit {
	display: flex;
	flex-wrap: wrap;
	align-items: end;
	.v-select {
		margin: 0 8px;
		width: unset !important;
		min-width: 75px;
		flex: 1;
	}

	.button-vue.button-vue--vue-tertiary {
		padding: 0;
		min-width: 0;
	}

	.button-vue__text,
	.button-vue--text-only .button-vue__text {
		margin: 0;
	}
}
</style>
