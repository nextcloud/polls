<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'

import { useAppSettingsStore } from '../../../stores/appSettings'
import CardDiv from '../../Base/modules/CardDiv.vue'

const appSettingsStore = useAppSettingsStore()
</script>

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch
			v-model="appSettingsStore.unrestrictedOwner"
			type="switch"
			@update:model-value="appSettingsStore.write()">
			{{ t('polls', 'Enable unrestricted owners globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!appSettingsStore.unrestrictedOwner" class="settings_details">
			<NcSelect
				v-model="appSettingsStore.unrestrictedOwnerGroups"
				:input-label="t('polls', 'Enable only for the following groups')"
				label="displayName"
				:options="appSettingsStore.groups"
				:user-select="true"
				:multiple="true"
				:loading="appSettingsStore.status.loadingGroups"
				:placeholder="t('polls', 'Leave empty to disable globally')"
				@update:model-value="appSettingsStore.write()"
				@search="appSettingsStore.loadGroups" />
		</div>
		<CardDiv type="info">
			<p>
				{{ t('polls', 'Effects on restricted owners:') }}
			</p>
			<ul>
				<li>
					{{
						t(
							'polls',
							'Anonymizing a poll of a restricted owner means that this poll is anonymous for everyone, including the owner.',
						)
					}}
				</li>
				<li>
					{{
						t(
							'polls',
							'Deleting and changing votes of participants is not possible',
						)
					}}
				</li>
			</ul>
		</CardDiv>
	</div>
</template>
