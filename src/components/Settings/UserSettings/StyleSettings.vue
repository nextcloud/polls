<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import { usePreferencesStore } from '../../../stores/preferences.ts'
const preferencesStore = usePreferencesStore()
</script>

<template>
	<div>
		<b> {{ t('polls', 'The style settings are still experimental!') }}</b>

		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.useNewPollDialogInNavigation"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{
					t(
						'polls',
						"Use modal 'Add poll ' dialog instead of embedded dialog in Navigation bar",
					)
				}}
			</NcCheckboxRadioSwitch>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.useNewPollInPollist"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{
					t(
						'polls',
						"Use 'Add poll ' as button in poll list instead of inside the navigation",
					)
				}}
			</NcCheckboxRadioSwitch>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.useCommentsAlternativeStyling"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Use alternative styling for the comments sidebar') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.useAlternativeStyling"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Use alternative vote page styling') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch
				v-model="preferencesStore.user.useCardsArrangement"
				type="switch"
				@update:model-value="preferencesStore.write()">
				{{ t('polls', 'Arrange description and card hints side by side') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>
