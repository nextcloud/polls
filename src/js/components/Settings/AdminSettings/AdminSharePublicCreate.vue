<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="allowPublicShares" type="switch">
			{{ t('polls', 'Enable public shares of polls globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!allowPublicShares" class="settings_details">
			<NcSelect v-model="publicSharesGroups"
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

export default {
	name: 'AdminSharePublicCreate',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		allowPublicShares: {
			get() {
				return this.appSettings.allowPublicShares
			},
			set(value) {
				this.writeValue({ allowPublicShares: value })
			},
		},
		publicSharesGroups: {
			get() {
				return this.appSettings.publicSharesGroups
			},
			set(value) {
				this.writeValue({ publicSharesGroups: value })
			},
		},
	},
}
</script>
