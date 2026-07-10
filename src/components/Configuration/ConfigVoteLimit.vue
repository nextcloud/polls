<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import InputDiv from '../Base/modules/InputDiv.vue'
import { useOptionsStore } from '../../stores/options.ts'
import { usePollStore } from '../../stores/poll.ts'

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
			@update:modelValue="validateLimit()">
			{{ t('polls', 'Limit "Yes" votes per participant') }}
		</NcCheckboxRadioSwitch>

		<InputDiv
			v-if="useLimit"
			v-model="pollStore.configuration.maxVotesPerUser"
			class="indented"
			type="number"
			inputmode="numeric"
			useNumModifiers
			:numMin="1"
			:numMax="optionsStore.options.length"
			numWrap
			@change="emit('change')" />
	</div>
</template>
