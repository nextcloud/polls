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
	<AppSettingsDialog :open.sync="show">
		<AppSettingsSection :title="t('polls', 'User Settings')">
			<FeatureSettings />
		</AppSettingsSection>

		<AppSettingsSection :title="t('polls', 'Experimental Styles')">
			<ExpertimantalSettings />
		</AppSettingsSection>
	</AppSettingsDialog>
</template>

<script>

import { AppSettingsDialog, AppSettingsSection } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import FeatureSettings from './FeatureSettings'
import ExpertimantalSettings from './ExpertimantalSettings'

export default {
	name: 'SettingsDlg',

	components: {
		AppSettingsDialog,
		AppSettingsSection,
		FeatureSettings,
		ExpertimantalSettings,
	},

	data() {
		return {
			show: false,
			calendars: [],
		}
	},

	watch: {
		show() {
			if (this.show === true) {
				this.$store.dispatch('settings/getCalendars')
					.then((response) => {
						this.calendars = response.data.calendars
					})
			}
		},
	},

	created() {
		subscribe('show-settings', () => {
			this.show = true
		})

	},

	beforeDestroy() {
		unsubscribe('show-settings')
	},
}
</script>
