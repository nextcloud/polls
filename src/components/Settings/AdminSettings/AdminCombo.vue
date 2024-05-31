<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="allowCombo" type="switch">
			{{ t('polls', 'Enable the usage of the combo view globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!allowCombo" class="settings_details">
			<NcSelect v-model="comboGroups"
				:input-label="t('polls','Enable only for the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally')"
				@search="loadGroups" />
		</div>
	</div>
</template>

<script>

import { NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { loadGroups, writeValue } from '../../../mixins/adminSettingsMixin.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminCombo',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		allowCombo: {
			get() {
				return this.appSettings.allowCombo
			},
			set(value) {
				this.writeValue({ allowCombo: value })
			},
		},
		comboGroups: {
			get() {
				return this.appSettings.comboGroups
			},
			set(value) {
				this.writeValue({ comboGroups: value })
			},
		},
	},
	
	methods: {
		t,
	}
}
</script>
