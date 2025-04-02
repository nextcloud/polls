<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div v-if="proposalsExist">
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
				variant="tertiary"
				@click="shiftDates(shift)">
				<template #icon>
					<SubmitIcon />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>

import { mapGetters } from 'vuex'
import { InputDiv } from '../Base/index.js'
import { NcButton, NcSelect } from '@nextcloud/vue'
import { dateUnits } from '../../mixins/dateMixins.js'
import SubmitIcon from 'vue-material-design-icons/ArrowRight.vue'

export default {
	name: 'OptionsDateShift',

	components: {
		InputDiv,
		NcButton,
		NcSelect,
		SubmitIcon,
	},

	mixins: [dateUnits],

	data() {
		return {
			shift: {
				step: 1,
				unit: { name: t('polls', 'Week'), value: 'week' },
			},
		}
	},

	computed: {
		...mapGetters({
			proposalsExist: 'options/proposalsExist',
		}),
	},

	methods: {
		shiftDates(payload) {
			this.$store.dispatch('options/shift', { shift: payload })
		},
	},
}

</script>

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
