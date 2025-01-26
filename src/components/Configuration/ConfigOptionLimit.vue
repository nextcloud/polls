<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

	import { InputDiv } from '../Base/index.js'

	import { usePollStore } from '../../stores/poll.ts'

	const pollStore = usePollStore()
	const useLimit = computed({
		get: () => !!pollStore.configuration.maxVotesPerOption,
		set: (value) => {
			pollStore.configuration.maxVotesPerOption = value ? 1 : 0
		},
	})

	function validateLimit() {
		if (!useLimit.value) {
			pollStore.configuration.maxVotesPerOption = 0
		} else if (pollStore.configuration.maxVotesPerOption < 1) {
			pollStore.configuration.maxVotesPerOption = 1
		}

		pollStore.write()
	}
</script>

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

		<NcCheckboxRadioSwitch v-if="pollStore.configuration.maxVotesPerOption"
			v-model="pollStore.configuration.hideBookedUp"
			class="indented"
			type="switch"
			@change="pollStore.write()">
			{{ t('polls', 'Hide not available Options') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>
