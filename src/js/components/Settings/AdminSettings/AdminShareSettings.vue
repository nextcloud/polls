<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="user_settings">
		<NcCheckboxRadioSwitch v-model:checked="publicSharesLimited" type="switch">
			{{ t('polls', 'Disallow public shares') }}
		</NcCheckboxRadioSwitch>
		<div v-if="publicSharesLimited" class="settings_details">
			<NcSelect v-model="publicSharesGroups"
				:input-label="t('polls','Allow public shares for the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disallow for all.')"
				@search="loadGroups" />
		</div>

		<NcCheckboxRadioSwitch v-model:checked="allAccessLimited" type="switch">
			{{ t('polls', 'Disallow openly accessible polls') }}
		</NcCheckboxRadioSwitch>

		<div v-if="allAccessLimited" class="settings_details">
			<h3>{{ t('polls','Allow creating openly accessible polls for the following groups') }}</h3>
			<NcSelect v-model="allAccessGroups"
				:input-label="t('polls','Allow creating openly accessible polls for the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disallow for all.')"
				@search="loadGroups" />
		</div>
	</div>
</template>

<script>

import { NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { loadGroups, writeValue } from '../../../mixins/adminSettingsMixin.js'

export default {
	name: 'AdminShareSettings',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		publicSharesLimited: {
			get() {
				return !this.appSettings.allowPublicShares
			},
			set(value) {
				this.writeValue({ allowPublicShares: !value })
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
		allAccessLimited: {
			get() {
				return !this.appSettings.allowAllAccess
			},
			set(value) {
				this.writeValue({ allowAllAccess: !value })
			},
		},
		allAccessGroups: {
			get() {
				return this.appSettings.allAccessGroups
			},
			set(value) {
				this.writeValue({ allAccessGroups: value })
			},
		},
	},
}
</script>
