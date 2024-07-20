<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { NcActions, NcActionButton, NcPopover, NcCheckboxRadioSwitch } from '@nextcloud/vue'
	import InformationIcon from 'vue-material-design-icons/InformationVariant.vue'
	import AutoReminderInformation from './AutoReminderInformation.vue'
	import { t } from '@nextcloud/l10n'
	import { usePollStore } from '../../stores/poll.ts'

	const pollStore = usePollStore()
</script>

<template>
	<div class="auto-reminder-switch">
		<NcCheckboxRadioSwitch v-model="pollStore.configuration.autoReminder" 
			type="switch"
			@update:model-value="pollStore.write()">
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

<style lang="scss">
	.auto-reminder-switch {
		display: flex;
		.information-icon {
			margin-left: 12px;
		}
	}
</style>
