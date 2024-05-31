<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="allowPollCreation" type="switch">
			{{ t('polls', 'Enable the poll creation globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!allowPollCreation" class="settings_details">
			<NcSelect v-model="pollCreationGroups"
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

import { loadGroups, writeValue } from '../../../mixins/adminSettingsMixin.js'
import { NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminPollCreation',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		allowPollCreation: {
			get() {
				return this.appSettings.allowPollCreation
			},
			set(value) {
				this.writeValue({ allowPollCreation: value })
			},
		},
		pollCreationGroups: {
			get() {
				return this.appSettings.pollCreationGroups
			},
			set(value) {
				this.writeValue({ pollCreationGroups: value })
			},
		},
	},
	
	methods: {
		t,
	},
}
</script>
