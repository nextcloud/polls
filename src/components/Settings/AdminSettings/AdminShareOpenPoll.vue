<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="allowAllAccess" type="switch">
			{{ t('polls', 'Enable the creation of openly accessible polls globally') }}
		</NcCheckboxRadioSwitch>

		<div v-if="!allowAllAccess" class="settings_details">
			<NcSelect v-model="allAccessGroups"
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
	name: 'AdminShareOpenPoll',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		allowAllAccess: {
			get() {
				return this.appSettings.allowAllAccess
			},
			set(value) {
				this.writeValue({ allowAllAccess: value })
			},
		},
		allAccessGroups: {
			get() {
				return this.appSettings.allAccessGroups
			},
			set(value) {
				this.writeValue({ allAccessGroups: value })
			},
		},
	},
	
	methods: {
		t,
	},
}
</script>
