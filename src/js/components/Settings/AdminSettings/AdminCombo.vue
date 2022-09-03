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
		<NcCheckboxRadioSwitch :checked.sync="comboLimited" type="switch">
			{{ t('polls', 'Deactivate combo view for all users') }}
		</NcCheckboxRadioSwitch>
		<div v-if="comboLimited" class="settings_details">
			<h3>{{ t('polls','Allow combo view for the following groups') }}</h3>
			<NcMultiselect v-model="comboGroups"
				class="stretch"
				label="displayName"
				track-by="id"
				:options="groups"
				:user-select="true"
				:clear-on-select="false"
				:preserve-search="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disallow for all.')"
				@search-change="loadGroups" />
		</div>
	</div>
</template>

<script>

import { NcCheckboxRadioSwitch, NcMultiselect } from '@nextcloud/vue'
import { loadGroups, writeValue } from '../../../mixins/adminSettingsMixin.js'

export default {
	name: 'AdminCombo',

	components: {
		NcCheckboxRadioSwitch,
		NcMultiselect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		comboLimited: {
			get() {
				return !this.appSettings.allowCombo
			},
			set(value) {
				this.writeValue({ allowCombo: !value })
			},
		},
		comboGroups: {
			get() {
				return this.appSettings.comboGroups
			},
			set(value) {
				this.writeValue({ comboGroups: value })
			},
		},
	},
}
</script>
