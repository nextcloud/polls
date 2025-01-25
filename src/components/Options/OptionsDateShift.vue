<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'
	import NcSelect from '@nextcloud/vue/components/NcSelect'

	import SubmitIcon from 'vue-material-design-icons/ArrowRight.vue'

	import { InputDiv } from '../Base/index.js'
	import { dateUnits, DateUnitValue, TimeUnits } from '../../constants/dateUnits.ts'
	import { useOptionsStore } from '../../stores/options.ts'
	import { usePollStore } from '../../stores/poll.ts'

	const pollStore = usePollStore()

	const optionsStore = useOptionsStore()

	const shift = ref<TimeUnits>({
		value: 1,
		unit: { name: t('polls', 'Week'), value: DateUnitValue.Week },
	})

	function shiftDates(shift: TimeUnits) {
		optionsStore.shift({ shift })
	}

</script>

<template>
	<div>
		<div v-if="pollStore.status.countProposals > 0">
			{{ t('polls', 'Shifting dates is disabled to prevent shifting of proposals of other participants.') }}
		</div>
		<div v-else class="select-unit">
			<InputDiv v-model="shift.value" class="shift-step" :label="t('polls', 'Step width')" use-num-modifiers />
			<div class="unit-select">
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
	</div>
</template>

<style lang="scss">
.select-unit {
	display: flex;
	flex-wrap: wrap;
	align-items: end;
	column-gap: 8px;

	.unit-select {
		flex: 1;
		display: flex;
		align-items: end;
		.v-select {
			flex: 1 185px;
			margin: 0 8px;
			width: unset !important;
			margin-bottom: var(--default-grid-baseline);
		}
		.button-vue {
			margin-bottom: var(--default-grid-baseline);
			flex: 0 auto;
		}
	}

	.button-vue__text,
	.button-vue--text-only .button-vue__text {
		margin: 0;
	}
}
</style>
