<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="allowPollDownload" type="switch">
			{{ t('polls', 'Enable the spreadsheet download of polls globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!allowPollDownload" class="settings_details">
			<NcSelect v-model="pollDownloadGroups"
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
	name: 'AdminPollDownload',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		allowPollDownload: {
			get() {
				return this.appSettings.allowPollDownload
			},
			set(value) {
				this.writeValue({ allowPollDownload: value })
			},
		},
		pollDownloadGroups: {
			get() {
				return this.appSettings.pollDownloadGroups
			},
			set(value) {
				this.writeValue({ pollDownloadGroups: value })
			},
		},
	},
	
	methods: {
		t,
	},
}
</script>
