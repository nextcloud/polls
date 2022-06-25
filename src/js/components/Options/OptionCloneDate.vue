<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="option-clone-date">
		<h2>{{ t('polls', 'Clone to option sequence') }}</h2>
		<p>{{ t('polls', 'Create a sequence of date options starting with {dateOption}.', { dateOption: dateBaseOptionString }) }}</p>

		<h3> {{ t('polls', 'Step unit') }} </h3>
		<Multiselect v-model="sequence.unit"
			:options="dateUnits"
			label="name"
			track-by="value" />

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
			<VueButton @click="$emit('close')">
				{{ t('polls', 'Cancel') }}
			</VueButton>

			<VueButton type="primary" @click="createSequence()">
				{{ t('polls', 'OK') }}
			</VueButton>
		</div>
	</div>
</template>

<script>

import moment from '@nextcloud/moment'
import { Button as VueButton, Multiselect } from '@nextcloud/vue'
import { dateUnits } from '../../mixins/dateMixins.js'
import InputDiv from '../Base/InputDiv.vue'

export default {
	name: 'OptionCloneDate',

	components: {
		InputDiv,
		Multiselect,
		VueButton,
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

</style>
