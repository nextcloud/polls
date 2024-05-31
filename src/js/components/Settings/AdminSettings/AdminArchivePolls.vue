<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="autoArchive" type="switch">
			{{ t('polls', 'Enable the automatic poll archiving') }}
		</NcCheckboxRadioSwitch>
		<InputDiv v-if="autoArchive"
			v-model="autoArchiveOffset"
			class="settings_details"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:label="t('polls', 'Days after which polls should be archived after closing')" />
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../../Base/index.js'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminArchivePolls',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	mixins: [writeValue],

	computed: {
		// Add bindings
		autoArchive: {
			get() {
				return this.appSettings.autoArchive
			},
			set(value) {
				this.writeValue({ autoArchive: value })
			},
		},
		autoArchiveOffset: {
			get() {
				return this.appSettings.autoArchiveOffset
			},
			set(value) {
				value = value < 1 ? 1 : value
				this.writeValue({ autoArchiveOffset: value })
			},
		},
	},
	
	methods: {
		t,
	},
}
</script>
