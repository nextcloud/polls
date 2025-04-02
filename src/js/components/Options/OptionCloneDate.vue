<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="option-clone-date">
		<h2>{{ t('polls', 'Clone to option sequence') }}</h2>
		<p>{{ t('polls', 'Create a sequence of date options starting with {dateOption}.', { dateOption: dateBaseOptionString }) }}</p>

		<NcSelect v-model="sequence.unit"
			:input-label="t('polls', 'Step unit')"
			:clearable="false"
			:filterable="false"
			:options="dateUnits"
			label="name" />

		<div class="sideways">
			<InputDiv v-model="sequence.step"
				:label="t('polls', 'Step width')"
				type="number"
				inputmode="numeric"
				use-num-modifiers />

			<InputDiv v-model="sequence.amount"
				:label="t('polls', 'Amount')"
				type="number"
				inputmode="numeric"
				use-num-modifiers />
		</div>

		<div class="modal__buttons">
			<NcButton @click="$emit('close')">
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

<script>

import moment from '@nextcloud/moment'
import { NcButton, NcSelect } from '@nextcloud/vue'
import { dateUnits } from '../../mixins/dateMixins.js'
import { InputDiv } from '../Base/index.js'

export default {
	name: 'OptionCloneDate',

	components: {
		InputDiv,
		NcSelect,
		NcButton,
	},

	mixins: [dateUnits],

	props: {
		option: {
			type: Object,
			default: undefined,
		},
	},

	data() {
		return {
			sequence: {
				unit: { name: t('polls', 'Week'), value: 'week' },
				step: 1,
				amount: 1,
			},
		}
	},

	computed: {
		dateBaseOptionString() {
			return moment.unix(this.option.timestamp).format('LLLL')
		},
	},

	methods: {
		createSequence() {
			this.$store
				.dispatch('options/sequence', {
					option: this.option,
					sequence: this.sequence,
				})
			this.$emit('close')
		},
	},
}

</script>

<style lang="scss">

.sideways {
	display: flex;
	column-gap: 48px;
	flex-wrap: wrap;
}

.option-clone-date {
	&>.v-select, &>.sideways {
		margin-top: 8px;
	}
}

</style>
