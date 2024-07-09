<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcCheckboxRadioSwitch v-model="useLimit" 
			type="switch"
			@update:model-value="validateLimit()">
			{{ t('polls', 'Limit "Yes" votes per option') }}
		</NcCheckboxRadioSwitch>

		<InputDiv v-if="useLimit"
			v-model="pollStore.configuration.maxVotesPerOption"
			class="indented"
			type="number"
			inputmode="numeric"
			:num-min="1"
			use-num-modifiers
			@change="pollStore.write()" />

		<NcCheckboxRadioSwitch v-if="maxVotesPerOption"
			class="indented"
			v-model="pollStore.configuration.hideBookedUp"
			type="switch"
			@change="pollsStore.write()">
			{{ t('polls', 'Hide not available Options') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigOptionLimit',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapStores(usePollStore),

		useLimit: {
			get() {
				return !!this.pollStore.configuration.maxVotesPerOption
			},
			set(value) {
				this.pollStore.configuration.maxVotesPerOption = value ? 1 : 0
			},
		},
	},

	
	methods: {
		t,
		validateLimit() {
			if (!this.useLimit) {
				this.pollStore.configuration.maxVotesPerOption = 0
			} else if (this.maxVotesPerOption < 1) {
				this.pollStore.configuration.maxVotesPerOption = 1
			}

			this.pollStore.write()
		},
		

	},
}
</script>
