<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch :checked.sync="appSettingsStore.autoArchive" type="switch"
		@update:checked="appSettingsStore.write()">
			{{ t('polls', 'Enable the automatic poll archiving') }}
		</NcCheckboxRadioSwitch>
		<InputDiv v-if="appSettingsStore.autoArchive"
			v-model="appSettingsStore.autoArchiveOffset"
			class="settings_details"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:label="t('polls', 'Days after which polls should be archived after closing')" 
			@change="appSettingsStore.write()"/>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { InputDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

export default {
	name: 'AdminArchivePolls',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapStores(useAppSettingsStore),
	},
	
	methods: {
		t,
	},
}
</script>
