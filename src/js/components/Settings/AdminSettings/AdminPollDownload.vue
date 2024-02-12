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
		<NcCheckboxRadioSwitch v-model:checked="pollDownloadLimited" type="switch">
			{{ t('polls', 'Disallow poll download') }}
		</NcCheckboxRadioSwitch>
		<div v-if="pollDownloadLimited" class="settings_details">
			<NcSelect v-model="pollDownloadGroups"
				:input-label="t('polls','Allow poll download for the following groups')"
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
	name: 'AdminPollDownload',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
	},

	mixins: [loadGroups, writeValue],

	computed: {
		// Add bindings
		pollDownloadLimited: {
			get() {
				return !this.appSettings.allowPollDownload
			},
			set(value) {
				this.writeValue({ allowPollDownload: !value })
			},
		},
		pollDownloadGroups: {
			get() {
				return this.appSettings.pollDownloadGroups
			},
			set(value) {
				this.writeValue({ pollDownloadGroups: value })
			},
		},
	},
}
</script>
