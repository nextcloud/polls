<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="showMailAddresses" type="switch">
			{{ t('polls', 'Show email addresses of internal accounts') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!showMailAddresses" class="settings_details">
			<NcSelect v-model="showMailAddressesGroups"
				:input-label="t('polls','Show only to members of the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally.')"
				@search="loadGroups" />
		</div>
	</div>
</template>

<script>

import { NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { loadGroups, writeValue } from '../../../mixins/adminSettingsMixin.js'

export default {
	name: 'AdminShowMailAddresses',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		showMailAddresses: {
			get() {
				return this.appSettings.showMailAddresses
			},
			set(value) {
				this.writeValue({ showMailAddresses: value })
			},
		},
		showMailAddressesGroups: {
			get() {
				return this.appSettings.showMailAddressesGroups
			},
			set(value) {
				this.writeValue({ showMailAddressesGroups: value })
			},
		},
	},
}
</script>
