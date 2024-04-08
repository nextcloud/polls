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
		<NcCheckboxRadioSwitch :checked.sync="showLogin" type="switch">
			{{ t('polls', 'Enable the login option in the registration dialog of public polls') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'

export default {
	name: 'AdminSharePublicShowLogin',

	components: {
		NcCheckboxRadioSwitch,
	},

	mixins: [writeValue],

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),

		// Add bindings
		showLogin: {
			get() {
				return this.appSettings.showLogin
			},
			set(value) {
				this.writeValue({ showLogin: value })
			},
		},
	},
}
</script>
