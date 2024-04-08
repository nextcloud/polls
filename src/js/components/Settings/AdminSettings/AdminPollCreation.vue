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
		<NcCheckboxRadioSwitch :checked.sync="allowPollCreation" type="switch">
			{{ t('polls', 'Enable the poll creation globally') }}
		</NcCheckboxRadioSwitch>
		<div v-if="!allowPollCreation" class="settings_details">
			<NcSelect v-model="pollCreationGroups"
				:input-label="t('polls','Enable only for the following groups')"
				label="displayName"
				:options="groups"
				:user-select="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disable globally')"
				@search="loadGroups" />
		</div>
	</div>
</template>

<script>

import { loadGroups, writeValue } from '../../../mixins/adminSettingsMixin.js'
import { NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'

export default {
	name: 'AdminPollCreation',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		allowPollCreation: {
			get() {
				return this.appSettings.allowPollCreation
			},
			set(value) {
				this.writeValue({ allowPollCreation: value })
			},
		},
		pollCreationGroups: {
			get() {
				return this.appSettings.pollCreationGroups
			},
			set(value) {
				this.writeValue({ pollCreationGroups: value })
			},
		},
	},
}
</script>
