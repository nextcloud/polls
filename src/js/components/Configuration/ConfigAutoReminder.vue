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
		<NcCheckboxRadioSwitch v-model:checked="autoReminder" type="switch">
			{{ t('polls', 'Use Autoreminder') }}
		</NcCheckboxRadioSwitch>
		<NcPopover>
			<template #trigger>
				<NcActions>
					<NcActionButton>
						<template #icon>
							<InformationIcon />
						</template>
						{{ t('polls', 'Autoreminder informations') }}
					</NcActionButton>
				</NcActions>
			</template>
			<AutoReminderInformation />
		</NcPopover>
	</div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { mapState } from 'vuex'
import { NcActions, NcActionButton, NcPopover, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import InformationIcon from 'vue-material-design-icons/InformationVariant.vue'

export default {
	name: 'ConfigAutoReminder',

	components: {
		NcCheckboxRadioSwitch,
		NcActions,
		NcActionButton,
		NcPopover,
		InformationIcon,
		AutoReminderInformation: defineAsyncComponent(() => import('./AutoReminderInformation.vue')),
	},

	emits: {
		change: null,
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
