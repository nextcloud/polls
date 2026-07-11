<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import InformationIcon from 'vue-material-design-icons/InformationVariant.vue'
import AutoReminderInformation from './AutoReminderInformation.vue'
import { usePollStore } from '../../stores/poll.ts'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
</script>

<template>
	<div class="auto-reminder-switch">
		<NcCheckboxRadioSwitch
			v-model="pollStore.configuration.autoReminder"
			type="switch"
			@update:modelValue="emit('change')">
			{{ t('polls', 'Use Autoreminder') }}
		</NcCheckboxRadioSwitch>
		<NcPopover noFocusTrap>
			<template #trigger>
				<NcButton
					variant="tertiary-no-background"
					:title="t('polls', 'Autoreminder information')"
					:aria-label="t('polls', 'Autoreminder information')">
					<template #icon>
						<InformationIcon />
					</template>
				</NcButton>
			</template>
			<AutoReminderInformation />
		</NcPopover>
	</div>
</template>

<style lang="scss">
.auto-reminder-switch {
	display: flex;
	.information-icon {
		margin-inline-start: 12px;
	}
}
</style>
