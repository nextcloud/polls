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
			<NcCheckboxRadioSwitch :checked.sync="useDashboardStyling" type="switch">
				{{ t('polls', 'Adopt dashboard style') }}
			</NcCheckboxRadioSwitch>
		</div>

		<div v-if="!useDashboardStyling" class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="useIndividualStyling" type="switch">
				{{ t('polls', 'Set individual styles') }}
			</NcCheckboxRadioSwitch>
		</div>

		<div v-if="useIndividualStyling && !useDashboardStyling">
			<div class="user_settings">
				<NcCheckboxRadioSwitch :checked.sync="individualBgColor" type="switch">
					{{ t('polls', 'Use background color') }}
				</NcCheckboxRadioSwitch>

				<NcCheckboxRadioSwitch :checked.sync="individualImage" type="switch">
					{{ t('polls', 'Use background image') }}
				</NcCheckboxRadioSwitch>

				<div v-if="individualImage" class="settings_details">
					<InputDiv v-model="individualImageUrl"
						type="text"
						:placeholder="t('polls', 'Enter the URL of your favorite background image.')" />
					<NcCheckboxRadioSwitch :checked.sync="individualImageStyle" type="switch">
						{{ t('polls', 'Dark picture') }}
					</NcCheckboxRadioSwitch>
				</div>
			</div>

			<div class="user_settings">
				<NcCheckboxRadioSwitch :checked.sync="translucentPanels" type="switch">
					{{ t('polls', 'Translucent foreground elements') }}
				</NcCheckboxRadioSwitch>

				<div class="settings_details">
					{{ t('polls', 'Add a translucent effect on foreground elements like sidebar and poll table (Does not work with all browsers).') }}
				</div>
			</div>
		</div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="useCommentsAlternativeStyling" type="switch">
				{{ t('polls', 'Use alternative styling for the comments sidebar') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import InputDiv from '../../Base/InputDiv.vue'

export default {
	name: 'StyleSettings',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		// Add bindings
		useDashboardStyling: {
			get() {
				return !!this.settings.useDashboardStyling
			},
			set(value) {
				this.writeValue({ useDashboardStyling: +value })
			},
		},

		useIndividualStyling: {
			get() {
				return !!this.settings.useIndividualStyling
			},
			set(value) {
				this.writeValue({ useIndividualStyling: +value })
			},
		},

		individualImage: {
			get() {
				return !!this.settings.individualImage
			},
			set(value) {
				this.writeValue({ individualImage: +value })
			},
		},

		individualImageStyle: {
			get() {
				return this.settings.individualImageStyle === 'light'
			},
			set(value) {
				this.writeValue({ individualImageStyle: value ? 'light' : 'dark' })
			},
		},

		individualBgColor: {
			get() {
				return !!this.settings.individualBgColor
			},
			set(value) {
				this.writeValue({ individualBgColor: +value })
			},
		},

		individualImageUrl: {
			get() {
				return this.settings.individualImageUrl
			},
			set(value) {
				this.writeValue({ individualImageUrl: value })
			},
		},

		translucentPanels: {
			get() {
				return !!this.settings.translucentPanels
			},
			set(value) {
				this.writeValue({ translucentPanels: +value })
			},
		},

		useCommentsAlternativeStyling: {
			get() {
				return !!this.settings.useCommentsAlternativeStyling
			},
			set(value) {
				this.writeValue({ useCommentsAlternativeStyling: +value })
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
