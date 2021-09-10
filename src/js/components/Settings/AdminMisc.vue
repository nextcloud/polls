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
		<CheckboxRadioSwitch :checked.sync="hideLogin" type="switch">
			{{ t('polls', 'Hide login option in public polls') }}
		</CheckboxRadioSwitch>
		<CheckboxRadioSwitch :checked.sync="autoArchive" type="switch">
			{{ t('polls', 'Archive closed polls automatically') }}
		</CheckboxRadioSwitch>
		<div v-if="autoArchive" class="settings_details">
			<span>{{ t('polls', 'After how many days are the closed polls to be archived:') }}</span>
			<InputDiv v-model="autoArchiveOffset"
				class="selectUnit"
				use-num-modifiers
				@add="autoArchiveOffset += 1"
				@subtract="autoArchiveOffset -= 1" />
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { CheckboxRadioSwitch } from '@nextcloud/vue'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'AdminMisc',

	components: {
		CheckboxRadioSwitch,
		InputDiv,
	},

	data() {
		return {
			searchToken: null,
			isLoading: false,
		}
	},

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings.appSettings,
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

<style lang="scss">
	.user_settings {
		padding-top: 16px;
	}

	.settings_details {
		padding-bottom: 16px;
		margin-left: 36px;
		input, .stretch {
			width: 100%;
		}
	}
</style>
