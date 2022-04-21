<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<div class="auto-reminder-switch">
		<CheckboxRadioSwitch :checked.sync="autoReminder" type="switch">
			{{ t('polls', 'Use Autoreminder') }}
		</CheckboxRadioSwitch>
		<Popover>
			<template #trigger>
				<Actions>
					<ActionButton icon="icon-info">
						{{ t('polls', 'Autoreminder informations') }}
					</ActionButton>
				</Actions>
			</template>
			<AutoReminderInformation />
		</Popover>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { Actions, ActionButton, Popover, CheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'ConfigAutoReminder',

	components: {
		CheckboxRadioSwitch,
		Actions,
		ActionButton,
		Popover,
		AutoReminderInformation: () => import('./AutoReminderInformation.vue'),
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		autoReminder: {
			get() {
				return !!this.poll.autoReminder
			},
			set(value) {
				this.$store.commit('poll/setProperty', { autoReminder: +value })
				this.$emit('change')
			},
		},

	},
}
</script>

<style lang="scss">
	.auto-reminder-switch {
		display: flex;
		.information-icon {
			margin-left: 12px;
		}
	}
</style>
