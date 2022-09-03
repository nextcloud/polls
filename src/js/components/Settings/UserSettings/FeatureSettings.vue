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
			<NcCheckboxRadioSwitch :checked.sync="defaultViewTextPoll" type="switch">
				{{ t('polls', 'Text polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display text poll in a vertical aligned list rather than in the grid view. The initial default is list view.') }}
			</div>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="defaultViewDatePoll" type="switch">
				{{ t('polls', 'Date polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display date poll in a vertical view rather than in the grid view. The initial default is grid view.') }}
			</div>
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'FeatureSettings',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		defaultViewTextPoll: {
			get() {
				return (this.settings.defaultViewTextPoll === 'list-view')
			},
			set(value) {
				if (value) {
					this.writeValue({ defaultViewTextPoll: 'list-view' })
				} else {
					this.writeValue({ defaultViewTextPoll: 'table-view' })
				}
			},
		},
		defaultViewDatePoll: {
			get() {
				return (this.settings.defaultViewDatePoll === 'list-view')
			},
			set(value) {
				if (value) {
					this.writeValue({ defaultViewDatePoll: 'list-view' })
				} else {
					this.writeValue({ defaultViewDatePoll: 'table-view' })
				}
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
