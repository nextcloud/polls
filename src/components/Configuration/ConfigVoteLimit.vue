<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import InputDiv from '../Base/modules/InputDiv.vue'

import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
const optionsStore = useOptionsStore()

const useLimit = computed({
	get: () => !!pollStore.configuration.maxVotesPerUser,
	set(value) {
		pollStore.configuration.maxVotesPerUser = value ? 1 : 0
	},
})

/**
 *
 */
function validateLimit() {
	if (!useLimit.value) {
		pollStore.configuration.maxVotesPerUser = 0
	} else if (pollStore.configuration.maxVotesPerUser < 1) {
		pollStore.configuration.maxVotesPerUser = 1
	} else if (
		pollStore.configuration.maxVotesPerUser > optionsStore.options.length
	) {
		pollStore.configuration.maxVotesPerUser = optionsStore.options.length
	}

	emit('change')
}
</script>

<template>
	<div>
		<NcCheckboxRadioSwitch
			v-model="useLimit"
			type="switch"
			@update:model-value="validateLimit()">
			{{ t('polls', 'Limit "Yes" votes per participant') }}
		</NcCheckboxRadioSwitch>

		<InputDiv
			v-if="useLimit"
			v-model="pollStore.configuration.maxVotesPerUser"
			class="indented"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:num-min="1"
			:num-max="optionsStore.options.length"
			num-wrap
			@change="emit('change')" />
	</div>
</template>
