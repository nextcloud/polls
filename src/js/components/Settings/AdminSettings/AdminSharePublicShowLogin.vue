<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="showLogin" type="switch">
			{{ t('polls', 'Enable the login option in the registration dialog of public polls') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'

export default {
	name: 'AdminSharePublicShowLogin',

	components: {
		NcCheckboxRadioSwitch,
	},

	mixins: [writeValue],

	computed: {
		// Add bindings
		showLogin: {
			get() {
				return this.appSettings.showLogin
			},
			set(value) {
				this.writeValue({ showLogin: value })
			},
		},
	},
}
</script>
