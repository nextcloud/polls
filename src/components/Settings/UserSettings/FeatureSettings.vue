<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { computed } from 'vue'
import InputDiv from '../../Base/modules/InputDiv.vue'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { usePreferencesStore } from '../../../stores/preferences.ts'

const preferencesStore = usePreferencesStore()

const defaultViewTextPoll = computed({
	get() {
		return preferencesStore.user.defaultViewTextPoll === 'list-view'
	},
	set(value) {
		preferencesStore.user.defaultViewTextPoll = value
			? 'list-view'
			: 'table-view'
	},
})

const defaultViewDatePoll = computed({
	get() {
		return preferencesStore.user.defaultViewDatePoll === 'list-view'
	},
	set(value) {
		preferencesStore.user.defaultViewDatePoll = value
			? 'list-view'
			: 'table-view'
	},
})
</script>

<template>
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="defaultViewTextPoll"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Text polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{
					t(
						'polls',
						'Check this, if you prefer to display text poll in a vertical aligned list rather than in the grid view. The initial default is list view.',
					)
				}}
			</div>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="defaultViewDatePoll"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Date polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{
					t(
						'polls',
						'Check this, if you prefer to display date poll in a vertical view rather than in the grid view. The initial default is grid view.',
					)
				}}
			</div>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.verbosePollsList"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Verbose poll list') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{
					t(
						'polls',
						'Check this for more poll information in the overview.',
					)
				}}
			</div>
		</div>

		<div class="user_settings">
			<InputDiv
				v-model="preferencesStore.user.relevantOffset"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				:label="
					t(
						'polls',
						'Enter the amount of days, polls without activity stay in the relevant list:',
					)
				"
				@change="preferencesStore.write()" />
		</div>
	</div>
</template>
