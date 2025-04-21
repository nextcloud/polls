<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { NcDialog } from '@nextcloud/vue'
import NcButton, { ButtonVariant } from '@nextcloud/vue/components/NcButton'

const showAnonDialog = ref(false)
const anonDialog = {
	message: t(
		'polls',
		'Once enabled, the anonymous setting cannot be reverted anymore.',
	),
	name: t('polls', 'Anonymize poll irrevocably'),

	buttons: [
		{
			label: t('polls', 'Cancel'),
			callback: () => {
				pollStore.configuration.anonymous = false
			},
		},
		{
			label: t('polls', 'Ok'),
			variant: ButtonVariant.Primary,
			callback: () => {
				lockAnonymous()
			},
		},
	],
}

const showLockAnonymous = computed(
	() =>
		pollStore.permissions.deanonymize
		&& pollStore.status.isAnonymous
		&& !pollStore.status.isRealAnonymous,
)

/**
 *
 * @param forceDialog
 */
function spawnConfirmationDialog(forceDialog: boolean = false) {
	if (forceDialog) {
		showAnonDialog.value = true
		return
	}
	pollStore.write()
}

/**
 *
 */
function lockAnonymous() {
	pollStore.LockAnonymous()
	pollStore.write()
}

const pollStore = usePollStore()
const disabledState = computed(
	() =>
		(pollStore.status.isAnonymous && !pollStore.permissions.deanonymize)
		|| pollStore.status.isRealAnonymous,
)
</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="pollStore.configuration.anonymous"
		type="switch"
		:disabled="disabledState"
		@update:model-value="
			spawnConfirmationDialog(!pollStore.permissions.deanonymize)
		">
		{{ t('polls', 'Anonymous poll') }}
	</NcCheckboxRadioSwitch>

	<NcButton
		v-if="showLockAnonymous"
		class="indented"
		@click="spawnConfirmationDialog(true)">
		{{ t('polls', 'Anonymize poll irrevocably') }}
	</NcButton>

	<NcDialog v-model:open="showAnonDialog" v-bind="anonDialog" />
</template>
