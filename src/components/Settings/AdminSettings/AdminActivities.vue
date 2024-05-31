<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="useActivity" type="switch">
			{{ t('polls', 'Enable the tracking of activities with the Activities app') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminActivities',

	components: {
		NcCheckboxRadioSwitch,
	},

	mixins: [writeValue],

	computed: {
		// Add bindings
		useActivity: {
			get() {
				return this.appSettings.useActivity
			},
			set(value) {
				this.writeValue({ useActivity: value })
			},
		},
	},
	
	methods: {
		t,
	},
}
</script>
