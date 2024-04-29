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
}
</script>
