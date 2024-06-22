<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<b> {{ t('polls', 'The style settings are still experimental!') }}</b>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="preferencesStore.user.useCommentsAlternativeStyling" 
				type="switch"
				@change="preferencesStore.write()">
				{{ t('polls', 'Use alternative styling for the comments sidebar') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="preferencesStore.user.useAlternativeStyling" 
				type="switch"
				@change="preferencesStore.write()">
				{{ t('polls', 'Use alternative vote page styling') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>

import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePreferencesStore } from '../../../stores/preferences.ts'

export default {
	name: 'StyleSettings',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapStores(usePreferencesStore),
	},

	methods: {
		t,
	},
}
</script>
