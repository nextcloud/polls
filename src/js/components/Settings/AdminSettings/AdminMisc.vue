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
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch v-model:checked="useActivity" type="switch">
				{{ t('polls', 'Track activities') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch v-model:checked="hideLogin" type="switch">
				{{ t('polls', 'Hide login option in public polls') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch v-model:checked="autoArchive" type="switch">
				{{ t('polls', 'Archive closed polls automatically') }}
			</NcCheckboxRadioSwitch>
			<InputDiv v-if="autoArchive"
				v-model="autoArchiveOffset"
				class="settings_details"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				:label="t('polls', 'After how many days are closed polls to be archived:')" />
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import InputDiv from '../../Base/InputDiv.vue'

export default {
	name: 'AdminMisc',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),

		// Add bindings
		hideLogin: {
			get() {
				return !this.appSettings.showLogin
			},
			set(value) {
				this.writeValue({ showLogin: !value })
			},
		},
		useActivity: {
			get() {
				return this.appSettings.useActivity
			},
			set(value) {
				this.writeValue({ useActivity: value })
			},
		},
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
		async writeValue(value) {
			await this.$store.commit('appSettings/set', value)
			this.$store.dispatch('appSettings/write')
		},
	},
}
</script>
