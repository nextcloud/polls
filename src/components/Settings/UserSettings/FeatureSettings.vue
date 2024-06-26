<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :model-value="defaultViewTextPoll" 
				type ="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Text polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display text poll in a vertical aligned list rather than in the grid view. The initial default is list view.') }}
			</div>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch :model-value="defaultViewDatePoll" 
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Date polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display date poll in a vertical view rather than in the grid view. The initial default is grid view.') }}
			</div>
		</div>

		<div class="user_settings">
			<InputDiv v-model="preferencesStore.user.relevantOffset"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				:label="t('polls', 'Enter the amount of days, polls without activity stay in the relevant list:')" 
				@change="preferencesStore.write()" />
		</div>
	</div>
</template>

<script>

import { defineComponent } from 'vue'
import { mapStores } from 'pinia'
import { InputDiv } from '../../Base/index.js'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePreferencesStore } from '../../../stores/preferences.ts'

export default defineComponent({
	name: 'FeatureSettings',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapStores(usePreferencesStore),

		defaultViewTextPoll: {
			get() {
				return this.preferencesStore.user.defaultViewTextPoll === 'list-view'
			},
			set(value) {
				this.preferencesStore.user.defaultViewTextPoll = value ? 'list-view' : 'table-view'
			},
		},
		defaultViewDatePoll: {
			get() {
				return this.preferencesStore.user.defaultViewDatePoll === 'list-view'
			},
			set(value) {
				this.preferencesStore.user.defaultViewDatePoll = value ? 'list-view' : 'table-view'
			},
		},
	},

	methods: {
		t,
	},
})
</script>
