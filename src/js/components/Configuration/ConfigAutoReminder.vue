<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="auto-reminder-switch">
		<NcCheckboxRadioSwitch :checked.sync="autoReminder" type="switch">
			{{ t('polls', 'Use Autoreminder') }}
		</NcCheckboxRadioSwitch>
		<NcPopover :focus-trap="false">
			<template #trigger>
				<NcActions>
					<NcActionButton :name="t('polls', 'Autoreminder informations')" :aria-label="t('polls', 'Autoreminder informations')">
						<template #icon>
							<InformationIcon />
						</template>
					</NcActionButton>
				</NcActions>
			</template>
			<AutoReminderInformation />
		</NcPopover>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { NcActions, NcActionButton, NcPopover, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import InformationIcon from 'vue-material-design-icons/InformationVariant.vue'
import AutoReminderInformation from './AutoReminderInformation.vue'

export default {
	name: 'ConfigAutoReminder',

	components: {
		NcCheckboxRadioSwitch,
		NcActions,
		NcActionButton,
		NcPopover,
		InformationIcon,
		AutoReminderInformation,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		autoReminder: {
			get() {
				return !!this.pollConfiguration.autoReminder
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
			margin-inline-start: 12px;
		}
	}
</style>
