<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { t } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	import NcPopover from '@nextcloud/vue/dist/Components/NcPopover.js'
	import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
	import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'

	import InformationIcon from 'vue-material-design-icons/InformationVariant.vue'
	import AutoReminderInformation from './AutoReminderInformation.vue'

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
