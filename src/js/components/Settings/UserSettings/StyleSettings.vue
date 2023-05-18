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
		<b> {{ t('polls', 'The style settings are still experimental!') }}</b>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="useCommentsAlternativeStyling" type="switch">
				{{ t('polls', 'Use alternative styling for the comments sidebar') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="useAlternativeStyling" type="switch">
				{{ t('polls', 'Use alternative vote page styling') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'StyleSettings',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		useCommentsAlternativeStyling: {
			get() {
				return !!this.settings.useCommentsAlternativeStyling
			},
			set(value) {
				this.writeValue({ useCommentsAlternativeStyling: +value })
			},
		},

		useAlternativeStyling: {
			get() {
				return !!this.settings.useAlternativeStyling
			},
			set(value) {
				this.writeValue({ useAlternativeStyling: +value })
			},
		},

	},

	methods: {
		async writeValue(value) {
			await this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},
	},
}
</script>
