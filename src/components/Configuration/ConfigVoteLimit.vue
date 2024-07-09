<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcCheckboxRadioSwitch v-model="useLimit" 
			type="switch"
			@update:model-value="validateLimit()">
			{{ t('polls', 'Limit "Yes" votes per participant') }}
		</NcCheckboxRadioSwitch>

		<InputDiv v-if="useLimit"
			v-model="pollStore.configuration.maxVotesPerUser"
			class="indented"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:num-min="1"
			:num-max="optionsStore.list.length"
			num-wrap
			@change="pollStore.write()"/>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'

export default {
	name: 'ConfigVoteLimit',
	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapStores(usePollStore, useOptionsStore),

		useLimit: {
			get() {
				return !!this.pollStore.configuration.maxVotesPerUser
			},
			set(value) {
				this.pollStore.configuration.maxVotesPerUser = value ? 1 : 0
			},
		},
	},
	
	methods: {
		t,
		validateLimit(useLimit) {
			if (!this.useLimit) {
				this.pollStore.configuration.maxVotesPerUser = 0
			} else if (this.pollStore.configuration.maxVotesPerUser < 1) {
				this.pollStore.configuration.maxVotesPerUser = 1
			} else if (this.pollStore.configuration.maxVotesPerUser > this.optionsStore.list.length) {
				this.pollStore.configuration.maxVotesPerUser = this.optionsStore.list.length
			}

			this.pollStore.write()
		},
	},
}
</script>
