<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="navigationPollsInList" type="switch">
			{{ t('polls', 'Load polls into the navigation.') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>

import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'

export default {
	name: 'AdminPollsInNavigation',

	components: {
		NcCheckboxRadioSwitch,
	},

	mixins: [writeValue],

	computed: {
		// Add bindings
		navigationPollsInList: {
			get() {
				return this.appSettings.navigationPollsInList
			},
			set(value) {
				if (value < 0) {
					value = 0
				}
				this.writeValue({ navigationPollsInList: value })
			},
		},
	},
}
</script>
